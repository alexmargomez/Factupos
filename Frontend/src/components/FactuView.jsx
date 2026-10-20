import React, { useState, useEffect } from 'react';
import Modal from './Modal';
import FactuposLogo from '../assets/factupos.svg';
import FactuPrint from './FactuPrint';
import { FaFileInvoice, FaPrint, FaTimes, FaUser, FaMotorcycle, FaWhatsapp } from 'react-icons/fa';

const FactuView = ({ id, customerID, vehicleID, total, saleItem, saleData }) => {
  const initialSale = saleItem || saleData;
  const token = localStorage.getItem('authToken');
  const API_URL = import.meta.env.VITE_API_URL;

  const [factura, setFactura] = useState(initialSale?.invoice || null);
  const [customer, setCustomer] = useState(initialSale?.customer || null);
  const [vehicle, setVehicle] = useState(initialSale?.vehicle || null);
  const [saleDetails, setSaleDetails] = useState(initialSale?.sale_details || initialSale?.saleDetails || []);
  const [services, setServices] = useState(initialSale?.services || []);
  const [products, setProducts] = useState({});
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [loadingDetails, setLoadingDetails] = useState(false);
  const [printTrigger, setPrintTrigger] = useState(null);

  // Solo si no vino el objeto invoice pre-cargado en initialSale, obtenemos el número de factura ligero
  useEffect(() => {
    if (initialSale?.invoice) {
      setFactura(initialSale.invoice);
      return;
    }
    let isMounted = true;
    const fetchInvoiceHeader = async () => {
      try {
        const response = await fetch(`${API_URL}/invoices/${id}`, {
          headers: { Authorization: `Bearer ${token}` },
        });
        if (response.ok) {
          const result = await response.json();
          if (isMounted && Array.isArray(result) && result.length > 0) {
            setFactura(result[0]);
            if (result[0].sale) {
              setCustomer((prev) => prev || result[0].sale.customer || null);
              setVehicle((prev) => prev || result[0].sale.vehicle || null);
              setSaleDetails(result[0].sale.sale_details || result[0].sale.saleDetails || []);
              setServices(result[0].sale.services || []);
            }
          }
        }
      } catch (error) {
        console.error('Error fetching factura:', error);
      }
    };
    fetchInvoiceHeader();
    return () => {
      isMounted = false;
    };
  }, [id, initialSale, API_URL, token]);

  // Cargar detalles adicionales ÚNICAMENTE cuando el usuario abre el modal
  useEffect(() => {
    if (!isModalOpen) return;

    const loadModalDetails = async () => {
      setLoadingDetails(true);
      try {
        const requests = [];

        if (!customer && customerID) {
          requests.push(
            fetch(`${API_URL}/customers/${customerID}`, {
              headers: { Authorization: `Bearer ${token}` },
            })
              .then((r) => (r.ok ? r.json() : null))
              .then((res) => res && setCustomer(res))
          );
        }

        if (!vehicle && vehicleID) {
          requests.push(
            fetch(`${API_URL}/vehicles/${vehicleID}`, {
              headers: { Authorization: `Bearer ${token}` },
            })
              .then((r) => (r.ok ? r.json() : null))
              .then((res) => res && setVehicle(res))
          );
        }

        if (services.length === 0) {
          requests.push(
            fetch(`${API_URL}/services/${id}`, {
              headers: { Authorization: `Bearer ${token}` },
            })
              .then((r) => (r.ok ? r.json() : []))
              .then((res) => Array.isArray(res) && setServices(res))
          );
        }

        if (saleDetails.length === 0) {
          requests.push(
            fetch(`${API_URL}/sale-details/${id}`, {
              headers: { Authorization: `Bearer ${token}` },
            })
              .then((r) => (r.ok ? r.json() : []))
              .then(async (details) => {
                if (Array.isArray(details)) {
                  setSaleDetails(details);
                  const missingProducts = details.filter(
                    (d) => !d.product && d.product_id
                  );
                  for (const detail of missingProducts) {
                    const pRes = await fetch(`${API_URL}/products/${detail.product_id}`, {
                      headers: { Authorization: `Bearer ${token}` },
                    });
                    if (pRes.ok) {
                      const pData = await pRes.json();
                      setProducts((prev) => ({
                        ...prev,
                        [detail.product_id]: pData.name,
                      }));
                    }
                  }
                }
              })
          );
        }

        await Promise.all(requests);
      } catch (error) {
        console.error('Error cargando detalles de factura:', error);
      } finally {
        setLoadingDetails(false);
      }
    };

    loadModalDetails();
  }, [isModalOpen, id, customerID, vehicleID, customer, vehicle, services.length, saleDetails.length, API_URL, token]);

  const formatPrice = (price) => {
    if (price == null || isNaN(price)) return '0';
    return Number(price).toLocaleString('es-CO');
  };

  const formatDateTime = (dateTime) => {
    if (!dateTime) return 'N/A';
    const date = new Date(dateTime);
    return isNaN(date) ? 'Fecha inválida' : date.toLocaleDateString('es-CO');
  };

  const invoiceLabel = factura?.invoice_number || `Venta #${id}`;
  const subtotalProductos = saleDetails.reduce(
    (sum, item) => sum + (Number(item.price_total) || 0),
    0
  );
  const subtotalServicios = services.reduce(
    (sum, item) => sum + (Number(item.price) || 0),
    0
  );

  const sendInvoiceWhatsApp = () => {
    const rawPhone = String(customer?.phone || '').replace(/\D/g, '');
    if (!rawPhone) {
      window.alert('Este cliente no tiene un número de teléfono/WhatsApp registrado.');
      return;
    }
    const phone = rawPhone.length === 10 ? `57${rawPhone}` : rawPhone;
    const custName = customer?.name || 'Cliente';
    const vehInfo = vehicle?.plate ? ` (${vehicle.plate} - ${vehicle.make} ${vehicle.model})` : '';

    let msg = `Hola *${custName}*, adjuntamos el resumen de tu factura *${invoiceLabel}* en *FactuPOS*${vehInfo}:\n\n`;
    if (services.length > 0) {
      msg += `🔧 *Servicios ($ ${formatPrice(subtotalServicios)}):*\n`;
      services.forEach((s) => {
        msg += `• ${s.date}: $ ${formatPrice(s.price)}\n`;
      });
      msg += `\n`;
    }
    if (saleDetails.length > 0) {
      msg += `📦 *Repuestos / Productos ($ ${formatPrice(subtotalProductos)}):*\n`;
      saleDetails.forEach((d) => {
        const pName = d.product?.name || products[d.product_id] || `Producto #${d.product_id}`;
        msg += `• ${d.quantity}x ${pName}: $ ${formatPrice(d.price_total)}\n`;
      });
      msg += `\n`;
    }
    msg += `💰 *TOTAL FACTURADO: $ ${formatPrice(total)}*\n\n¡Gracias por tu compra!`;

    window.open(
      `https://wa.me/${phone}?text=${encodeURIComponent(msg)}`,
      '_blank',
      'noopener,noreferrer'
    );
  };

  return (
    <>
      <button
        type="button"
        onClick={() => setIsModalOpen(true)}
        className="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-sky-50 hover:bg-[#023047] text-[#023047] hover:text-[#ffb703] font-bold text-xs sm:text-sm border border-sky-200 transition shadow-2xs"
      >
        <FaFileInvoice />
        <span>{invoiceLabel}</span>
      </button>

      <Modal show={isModalOpen} onClose={() => setIsModalOpen(false)}>
        <div className="flex flex-col max-h-[90vh] w-full overflow-hidden">
          {/* Encabezado del Modal */}
          <div className="px-6 py-4 bg-[#023047] text-white flex items-center justify-between shrink-0">
            <div className="flex items-center gap-3">
              <div className="p-2 rounded-lg bg-white/10 text-[#ffb703]">
                <FaFileInvoice className="text-xl" />
              </div>
              <div>
                <h2 className="font-extrabold text-lg sm:text-xl tracking-wide">
                  FACTURA DE VENTA N° {invoiceLabel}
                </h2>
                <p className="text-xs text-sky-200">
                  Fecha de emisión: {formatDateTime(factura?.created_at || saleItem?.created_at)}
                </p>
              </div>
            </div>
            <button
              type="button"
              onClick={() => setIsModalOpen(false)}
              className="p-2 rounded-lg text-slate-300 hover:text-white hover:bg-white/10 transition"
            >
              <FaTimes className="text-lg" />
            </button>
          </div>

          {/* Cuerpo desplazable */}
          <div className="p-6 overflow-y-auto space-y-5 flex-1 bg-slate-50">
            {/* Tarjetas de Empresa, Cliente y Vehículo */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div className="bg-white p-4 rounded-xl border border-slate-200 flex flex-col items-center justify-center text-center shadow-2xs">
                <img
                  src={FactuposLogo}
                  alt="FactuPOS"
                  className="h-14 object-contain mb-2"
                />
                <span className="text-xs font-bold text-[#023047] uppercase">
                  FactuPOS
                </span>
                <span className="text-[11px] text-slate-500">
                  Servicio Técnico y Repuestos
                </span>
              </div>

              <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-2xs">
                <div className="flex items-center gap-2 text-xs font-bold uppercase text-[#023047] border-b border-slate-100 pb-2 mb-2">
                  <FaUser className="text-[#219ebc]" />
                  <span>Datos del Cliente</span>
                </div>
                <div className="space-y-1 text-xs sm:text-sm text-slate-700">
                  <p>
                    <strong className="text-slate-500">Nombre:</strong>{' '}
                    <span className="font-semibold text-slate-900">
                      {customer?.name || 'Cliente Mostrador'}
                    </span>
                  </p>
                  <p>
                    <strong className="text-slate-500">Teléfono:</strong>{' '}
                    {customer?.phone || 'No registrado'}
                  </p>
                  <p className="truncate">
                    <strong className="text-slate-500">Correo:</strong>{' '}
                    {customer?.email || 'No registrado'}
                  </p>
                </div>
              </div>

              <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-2xs">
                <div className="flex items-center gap-2 text-xs font-bold uppercase text-[#023047] border-b border-slate-100 pb-2 mb-2">
                  <FaMotorcycle className="text-[#fb8500] text-base" />
                  <span>Datos del Vehículo</span>
                </div>
                {vehicle ? (
                  <div className="space-y-1 text-xs sm:text-sm text-slate-700">
                    <p>
                      <strong className="text-slate-500">Placa:</strong>{' '}
                      <span className="font-mono font-bold bg-amber-100 text-amber-900 px-2 py-0.5 rounded">
                        {vehicle.plate}
                      </span>
                    </p>
                    <p>
                      <strong className="text-slate-500">Marca:</strong> {vehicle.make}
                    </p>
                    <p>
                      <strong className="text-slate-500">Modelo:</strong> {vehicle.model}
                    </p>
                  </div>
                ) : (
                  <p className="text-xs text-slate-400 italic py-4">
                    Venta de mostrador sin vehículo asociado
                  </p>
                )}
              </div>
            </div>

            {/* Tablas de Detalles */}
            {loadingDetails ? (
              <div className="py-8 text-center text-slate-500 text-sm">
                Cargando detalles de la factura...
              </div>
            ) : (
              <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
                {/* Servicios */}
                <div className="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-2xs flex flex-col">
                  <div className="bg-[#023047] text-white px-4 py-2.5 font-bold text-xs uppercase tracking-wider flex justify-between items-center">
                    <span>Mano de Obra / Servicios ({services.length})</span>
                    <span className="text-[#ffb703]">$ {formatPrice(subtotalServicios)}</span>
                  </div>
                  <div className="p-3 flex-1 overflow-y-auto max-h-60">
                    {services.length === 0 ? (
                      <p className="text-xs text-slate-400 text-center py-6">
                        No se registraron servicios en esta factura
                      </p>
                    ) : (
                      <table className="w-full text-left text-xs sm:text-sm">
                        <thead>
                          <tr className="border-b border-slate-200 text-slate-500 font-bold">
                            <th className="py-1.5 px-2">Descripción</th>
                            <th className="py-1.5 px-2 text-right">Valor</th>
                          </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                          {services.map((service) => (
                            <tr key={service.id}>
                              <td className="py-2 px-2 text-slate-800 font-medium">
                                {service.date}
                              </td>
                              <td className="py-2 px-2 text-right font-bold text-[#023047] whitespace-nowrap">
                                $ {formatPrice(service.price)}
                              </td>
                            </tr>
                          ))}
                        </tbody>
                      </table>
                    )}
                  </div>
                </div>

                {/* Productos */}
                <div className="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-2xs flex flex-col">
                  <div className="bg-[#023047] text-white px-4 py-2.5 font-bold text-xs uppercase tracking-wider flex justify-between items-center">
                    <span>Productos y Repuestos ({saleDetails.length})</span>
                    <span className="text-[#ffb703]">$ {formatPrice(subtotalProductos)}</span>
                  </div>
                  <div className="p-3 flex-1 overflow-y-auto max-h-60">
                    {saleDetails.length === 0 ? (
                      <p className="text-xs text-slate-400 text-center py-6">
                        No se registraron productos en esta factura
                      </p>
                    ) : (
                      <table className="w-full text-left text-xs sm:text-sm">
                        <thead>
                          <tr className="border-b border-slate-200 text-slate-500 font-bold">
                            <th className="py-1.5 px-2">Producto</th>
                            <th className="py-1.5 px-2 text-center">Cant.</th>
                            <th className="py-1.5 px-2 text-right">Subtotal</th>
                          </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                          {saleDetails.map((detail, index) => (
                            <tr key={detail.id || index}>
                              <td className="py-2 px-2 text-slate-800 font-medium">
                                {detail.product?.name ||
                                  products[detail.product_id] ||
                                  `Producto #${detail.product_id}`}
                              </td>
                              <td className="py-2 px-2 text-center font-semibold text-slate-600">
                                {detail.quantity}
                              </td>
                              <td className="py-2 px-2 text-right font-bold text-[#023047] whitespace-nowrap">
                                $ {formatPrice(detail.price_total)}
                              </td>
                            </tr>
                          ))}
                        </tbody>
                      </table>
                    )}
                  </div>
                </div>
              </div>
            )}
          </div>

          {/* Pie del Modal: Total y Acciones */}
          <div className="px-6 py-4 bg-white border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-4 shrink-0">
            <div className="flex items-baseline gap-2">
              <span className="text-sm font-bold uppercase text-slate-500">
                Total Facturado:
              </span>
              <span className="text-2xl sm:text-3xl font-black text-[#023047]">
                $ {formatPrice(total)}
              </span>
            </div>

            <div className="flex flex-wrap items-center gap-2.5 w-full sm:w-auto justify-end">
              <button
                type="button"
                onClick={sendInvoiceWhatsApp}
                className="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm transition shadow-sm"
                title="Enviar resumen de factura por WhatsApp"
              >
                <FaWhatsapp className="text-base" />
                <span>Enviar WhatsApp</span>
              </button>

              {factura?.id && (
                <button
                  type="button"
                  onClick={() => setPrintTrigger(Date.now())}
                  className="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#023047] hover:bg-[#fb8500] text-white font-bold text-sm transition shadow-sm"
                >
                  <FaPrint />
                  <span>Imprimir Factura</span>
                </button>
              )}
              {printTrigger && factura?.id && (
                <FactuPrint key={printTrigger} id={factura.id} />
              )}
              <button
                type="button"
                onClick={() => setIsModalOpen(false)}
                className="px-5 py-2.5 rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-800 font-bold text-sm transition"
              >
                Cerrar
              </button>
            </div>
          </div>
        </div>
      </Modal>
    </>
  );
};

export default FactuView;