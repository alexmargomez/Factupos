import React, { useState, useEffect } from 'react';
import DateTimeDisplay from '../components/DateTimeDisplay';
import LookProduct from '../components/LookProduct';
import DatesRegister from '../components/DatesRegister';
import FactuPrint from '../components/FactuPrint';
import Ready from '../components/Ready';
import DatesWorker from '../services/DatesWorker';
import {
  FaSearch,
  FaTrashAlt,
  FaPlus,
  FaMinus,
  FaTools,
  FaBoxOpen,
  FaFileInvoiceDollar,
  FaUserTie,
  FaMoneyBillWave,
} from 'react-icons/fa';

const Sale = () => {
  const API_URL = import.meta.env.VITE_API_URL;
  const token = localStorage.getItem('authToken');
  const [showModal, setShowModal] = useState(false);
  const [worker, setWorker] = useState('');
  const [datesWorker, setDatesWorker] = useState([]);
  const [invoices, setInvoices] = useState(null);
  const [PrintId, setPrintId] = useState(null);
  const [searchTerm, setSearchTerm] = useState('');
  const [services, setServices] = useState([]);
  const [servicesDate, setServicesDate] = useState([]);
  const [productsDate, setProductsDate] = useState([]);
  const [serviceInput, setServiceInput] = useState('');
  const [serviceValue, setServiceValue] = useState('');
  const [activeTab, setActiveTab] = useState('producto');
  const [paymentMethod, setPaymentMethod] = useState('Efectivo');
  const [total, setTotal] = useState(0);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [formError, setFormError] = useState('');

  const [clientValues, setClientValues] = useState({
    id: '',
    name: '',
    phone: '',
    email: '',
  });
  const [vehicleValues, setVehicleValues] = useState({
    id: '',
    plate: '',
    make: '',
    model: '',
  });
  const [clientData, setClientData] = useState([]);
  const [vehicleData, setVehicleData] = useState([]);

  const handleTabClick = (tab) => {
    setActiveTab(tab);
  };

  const addService = () => {
    const cleanDesc = serviceInput.trim();
    const numericPrice = parseInt(serviceValue, 10);
    if (!cleanDesc) {
      setFormError('Ingresa la descripción del servicio.');
      return;
    }
    if (isNaN(numericPrice) || numericPrice < 0) {
      setFormError('Ingresa un valor numérico válido para el servicio.');
      return;
    }
    setFormError('');
    setServices((prev) => [...prev, { date: cleanDesc, price: numericPrice }]);
    setServiceInput('');
    setServiceValue('');
  };

  const removeService = (index) => {
    setServices((prev) => prev.filter((_, i) => i !== index));
  };

  const handlePriceChange = (index, price) => {
    const parsed = price === '' ? 0 : Math.max(0, parseInt(price, 10) || 0);
    setServices((prev) =>
      prev.map((service, i) => (i === index ? { ...service, price: parsed } : service))
    );
  };

  const addProduct = (product) => {
    setFormError('');
    const qtyToAdd = Math.max(1, parseInt(product.und, 10) || 1);
    const unitPrice = Number(product.price) || 0;

    setProductsDate((prev) => {
      const existingIndex = prev.findIndex((p) => Number(p.id) === Number(product.id));
      if (existingIndex >= 0) {
        return prev.map((item, idx) => {
          if (idx !== existingIndex) return item;
          const nextUnd = (Number(item.und) || 1) + qtyToAdd;
          return {
            ...item,
            und: nextUnd,
            total: nextUnd * unitPrice,
          };
        });
      }
      return [
        ...prev,
        {
          ...product,
          und: qtyToAdd,
          total: qtyToAdd * unitPrice,
        },
      ];
    });
  };

  const updateProductQty = (index, delta) => {
    setProductsDate((prev) =>
      prev.map((item, idx) => {
        if (idx !== index) return item;
        const nextUnd = Math.max(1, (Number(item.und) || 1) + delta);
        return {
          ...item,
          und: nextUnd,
          total: nextUnd * (Number(item.price) || 0),
        };
      })
    );
  };

  const removeProduct = (index) => {
    setProductsDate((prev) => prev.filter((_, i) => i !== index));
  };

  // Cargar servicios cuando provienen de una cita pendiente en DatesRegister
  useEffect(() => {
    if (Array.isArray(servicesDate) && servicesDate.length > 0) {
      const mapped = servicesDate.map((data) =>
        typeof data === 'object' && data !== null
          ? { date: data.date || '', price: Number(data.price) || 0 }
          : { date: String(data), price: 0 }
      );
      setServices(mapped);
    }
  }, [servicesDate]);

  const subtotalProducts = productsDate.reduce(
    (acc, product) => acc + (Number(product.total) || 0),
    0
  );
  const subtotalServices = services.reduce(
    (acc, service) => acc + (Number(service.price) || 0),
    0
  );

  useEffect(() => {
    setTotal(subtotalServices + subtotalProducts);
  }, [subtotalServices, subtotalProducts]);

  const formatPrice = (price) => {
    return (Number(price) || 0).toLocaleString('es-CO');
  };

  const handleClearSale = () => {
    setProductsDate([]);
    setServices([]);
    setServicesDate([]);
    setFormError('');
  };

  // Creación de Factura, Venta, Detalles y Servicios
  const handleInvoice = async () => {
    setFormError('');

    if (!clientValues.name || !clientValues.name.trim()) {
      setFormError('Por favor ingresa o selecciona el nombre del cliente antes de facturar.');
      return;
    }

    if (productsDate.length === 0 && services.length === 0) {
      setFormError('Agrega al menos un producto o servicio a la factura.');
      return;
    }

    setIsSubmitting(true);

    try {
      let customerId = clientValues.id;
      let vehicleId = vehicleValues.id || null;
      let totalServicio = 0;

      const existingClient = customerId
        ? clientData.find((client) => Number(client.id) === Number(customerId))
        : clientData.find(
            (client) =>
              (client.name || '').trim().toLowerCase() ===
              clientValues.name.trim().toLowerCase()
          );

      if (existingClient) {
        customerId = existingClient.id;
      } else {
        const clienteRes = await fetch(`${API_URL}/customers/`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${token}`,
          },
          body: JSON.stringify({
            name: clientValues.name.trim(),
            phone: clientValues.phone?.trim() || null,
            email: clientValues.email?.trim() || null,
          }),
        });

        if (!clienteRes.ok) {
          const errorData = await clienteRes.text();
          console.error('Error creating customer:', errorData);
          throw new Error('No se pudo registrar el cliente. Verifica correo y teléfono.');
        }

        const newCustomer = await clienteRes.json();
        customerId = newCustomer.id;
      }

      // Verificar o crear vehículo si el usuario ingresó placa
      if (vehicleValues.plate && vehicleValues.plate.trim() !== '') {
        const existingVehicle = vehicleId
          ? vehicleData.find((v) => Number(v.id) === Number(vehicleId))
          : vehicleData.find(
              (v) =>
                (v.plate || '').trim().toUpperCase() ===
                vehicleValues.plate.trim().toUpperCase()
            );

        if (existingVehicle) {
          vehicleId = existingVehicle.id;
        } else {
          const vehicleResponse = await fetch(`${API_URL}/vehicles/`, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Authorization': `Bearer ${token}`,
            },
            body: JSON.stringify({
              plate: vehicleValues.plate.trim().toUpperCase(),
              make: vehicleValues.make?.trim() || 'General',
              model: vehicleValues.model?.trim() || 'General',
              customer_id: customerId,
            }),
          });

          if (!vehicleResponse.ok) {
            throw new Error('Error registrando los datos del vehículo.');
          }
          const createdVehicle = await vehicleResponse.json();
          vehicleId = createdVehicle.id;
        }
      }

      // Si el cliente tenía agendamiento pendiente, marcarlo como Completado
      try {
        const scheduleExists = await fetch(`${API_URL}/schedules/${customerId}`, {
          method: 'GET',
          headers: {
            'Authorization': `Bearer ${token}`,
          },
        });
        if (scheduleExists.ok) {
          const scheduleList = await scheduleExists.json();
          if (Array.isArray(scheduleList) && scheduleList.length > 0) {
            const responsePendiente = scheduleList.find(
              (schedule) => schedule.state === 'Pendiente'
            );
            if (responsePendiente) {
              await fetch(`${API_URL}/schedules/${responsePendiente.id}`, {
                method: 'PUT',
                headers: {
                  'Content-Type': 'application/json',
                  'Authorization': `Bearer ${token}`,
                },
                body: JSON.stringify({
                  servicios: services.map((s) => s.date),
                  worker_id: worker || responsePendiente.worker_id,
                  state: 'Completado',
                }),
              });
            }
          }
        }
      } catch (schedErr) {
        console.warn('No se pudo actualizar estado de agendamiento:', schedErr);
      }

      // Crear Venta
      const saleCreate = await fetch(`${API_URL}/sales/`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`,
        },
        body: JSON.stringify({
          customer_id: customerId,
          vehicle_id: vehicleId || null,
          worker_id: worker ? Number(worker) : null,
          total: total,
          payment_method: paymentMethod,
        }),
      });

      if (!saleCreate.ok) {
        throw new Error('Error creando el registro de la venta.');
      }

      const saleData = await saleCreate.json();
      const saleId = saleData.id;

      // Crear detalles de productos (descuenta stock en backend)
      for (const product of productsDate) {
        const saleDetail = await fetch(`${API_URL}/sale-details/`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${token}`,
          },
          body: JSON.stringify({
            customer_id: customerId,
            sale_id: saleId,
            product_id: product.id,
            quantity: product.und,
            price_total: product.total,
          }),
        });
        if (!saleDetail.ok) {
          throw new Error(`Error registrando el producto ${product.name}.`);
        }
      }

      // Crear servicios si existen (requiere vehicleId en tabla Services)
      if (services.length > 0) {
        let serviceVehicleId = vehicleId;
        if (!serviceVehicleId) {
          // Si se facturó un servicio sin placa, crear o usar un vehículo genérico del cliente
          const fallbackVeh = await fetch(`${API_URL}/vehicles/`, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Authorization': `Bearer ${token}`,
            },
            body: JSON.stringify({
              plate: 'SIN-PLACA',
              make: 'Mostrador',
              model: 'N/A',
              customer_id: customerId,
            }),
          });
          if (fallbackVeh.ok) {
            const fbData = await fallbackVeh.json();
            serviceVehicleId = fbData.id;
          }
        }

        for (const service of services) {
          const serviceCreate = await fetch(`${API_URL}/services/`, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Authorization': `Bearer ${token}`,
            },
            body: JSON.stringify({
              customer_id: customerId,
              vehicle_id: serviceVehicleId,
              sale_id: saleId,
              date: service.date,
              price: Number(service.price) || 0,
            }),
          });
          if (!serviceCreate.ok) {
            throw new Error(`Error registrando el servicio ${service.date}.`);
          }
          totalServicio += Number(service.price) || 0;
        }
      }

      // Crear Factura y obtener su ID real antes de imprimir
      const invoiceSale = await fetch(`${API_URL}/invoices/`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`,
        },
        body: JSON.stringify({
          sale_id: saleId,
        }),
      });

      if (!invoiceSale.ok) {
        throw new Error('Error generando el consecutivo de factura.');
      }

      const invoiceData = await invoiceSale.json();
      setInvoices(invoiceData.id);
      setPrintId(Date.now()); // Dispara FactuPrint únicamente cuando ya tenemos invoiceData.id

      // Actualizar acumulado del técnico si aplica
      if (worker && totalServicio > 0) {
        await fetch(`${API_URL}/workers/${worker}`, {
          method: 'PUT',
          headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${token}`,
          },
          body: JSON.stringify({
            totalDia: totalServicio,
          }),
        });
      }

      setShowModal(true);
    } catch (error) {
      console.error('Error creando Factura:', error);
      setFormError(error.message || 'Ocurrió un error al procesar la factura.');
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleCloseModal = () => {
    setShowModal(false);
    window.location.reload();
  };

  DatesWorker(API_URL, token, setDatesWorker);

  const selectedWorkerObj = datesWorker.find(
    (w) => Number(w.id) === Number(worker)
  );

  return (
    <div className="min-h-screen w-full flex flex-col bg-slate-100 p-3 md:p-5">
      {/* Encabezado Superior */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
        <div>
          <h1 className="text-2xl font-extrabold text-[#023047] tracking-tight flex items-center gap-2">
            <FaFileInvoiceDollar className="text-[#fb8500]" />
            <span>PUNTO DE VENTA — FACTURACIÓN</span>
          </h1>
          <p className="text-xs sm:text-sm text-slate-500">
            Selecciona o registra al cliente, agrega productos o servicios y genera la factura
          </p>
        </div>
        <DateTimeDisplay />
      </div>

      {/* Contenedor Principal */}
      <div
        className={`flex-1 bg-white rounded-2xl shadow-sm border border-slate-200 border-t-4 border-t-[#ffb703] p-4 flex flex-col gap-4 ${
          showModal ? 'animate-border' : ''
        }`}
      >
        {/* Mensaje de error de validación */}
        {formError && (
          <div className="p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-sm font-semibold flex items-center justify-between">
            <span>{formError}</span>
            <button
              type="button"
              onClick={() => setFormError('')}
              className="text-xs underline ml-4"
            >
              Cerrar
            </button>
          </div>
        )}

        {/* Sección 1: Cliente, Vehículo y Empleado/Método de Pago */}
        <section className="flex flex-col gap-3">
          <DatesRegister
            token={token}
            API_URL={API_URL}
            pent={setWorker}
            setClientData={setClientData}
            setVehicleData={setVehicleData}
            setClientValues={setClientValues}
            setVehicleValues={setVehicleValues}
            setServices={setServicesDate}
          />

          <div className="flex flex-wrap items-center justify-between gap-3 bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5">
            <div className="flex flex-wrap items-center gap-3">
              <div className="flex items-center gap-2 text-sm font-bold text-[#023047]">
                <FaUserTie className="text-[#219ebc]" />
                <span>Técnico / Empleado:</span>
              </div>
              <select
                value={worker || ''}
                onChange={(e) => setWorker(e.target.value)}
                className="px-3 py-1.5 text-sm border border-slate-300 rounded-lg bg-white text-slate-800 font-medium focus:outline-none focus:ring-2 focus:ring-[#219ebc]"
              >
                <option value="">Sin técnico asignado (Mostrador)</option>
                {datesWorker.map((w) => (
                  <option key={w.id} value={w.id}>
                    {w.name}
                  </option>
                ))}
              </select>
              {selectedWorkerObj && (
                <span className="text-xs bg-sky-100 text-sky-800 px-2.5 py-1 rounded-full font-semibold">
                  Asignado: {selectedWorkerObj.name}
                </span>
              )}
            </div>

            <div className="flex items-center gap-3">
              <div className="flex items-center gap-2 text-sm font-bold text-[#023047]">
                <FaMoneyBillWave className="text-emerald-600" />
                <span>Método de Pago:</span>
              </div>
              <select
                value={paymentMethod}
                onChange={(e) => setPaymentMethod(e.target.value)}
                className="px-3 py-1.5 text-sm border border-slate-300 rounded-lg bg-white text-slate-800 font-semibold focus:outline-none focus:ring-2 focus:ring-[#219ebc]"
              >
                <option value="Efectivo">Efectivo</option>
                <option value="Transferencia / Nequi">Transferencia / Nequi</option>
                <option value="Tarjeta">Tarjeta</option>
              </select>
            </div>
          </div>
        </section>

        {/* Sección 2: Catálogo (Izquierda) y Carrito de Facturación (Derecha) */}
        <section className="grid grid-cols-1 lg:grid-cols-12 gap-4 flex-1 min-h-[420px]">
          {/* Columna Izquierda: Catálogo de Productos y Servicios */}
          <div className="lg:col-span-6 border border-slate-200 rounded-xl flex flex-col overflow-hidden bg-white shadow-2xs">
            {/* Pestañas */}
            <div className="grid grid-cols-2 bg-[#023047] p-1 gap-1">
              <button
                type="button"
                onClick={() => handleTabClick('producto')}
                className={`py-2.5 px-4 rounded-lg font-bold text-sm flex items-center justify-center gap-2 transition ${
                  activeTab === 'producto'
                    ? 'bg-white text-[#023047] shadow-xs'
                    : 'text-slate-200 hover:bg-white/10'
                }`}
              >
                <FaBoxOpen />
                <span>Catálogo de Productos</span>
              </button>

              <button
                type="button"
                onClick={() => handleTabClick('servicio')}
                className={`py-2.5 px-4 rounded-lg font-bold text-sm flex items-center justify-center gap-2 transition ${
                  activeTab === 'servicio'
                    ? 'bg-white text-[#023047] shadow-xs'
                    : 'text-slate-200 hover:bg-white/10'
                }`}
              >
                <FaTools />
                <span>Mano de Obra / Servicio</span>
              </button>
            </div>

            {/* Contenido de la pestaña */}
            <div className="p-4 flex-1 flex flex-col overflow-hidden">
              {activeTab === 'producto' ? (
                <div className="flex flex-col gap-3 h-full">
                  <div className="relative">
                    <FaSearch className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
                    <input
                      type="text"
                      placeholder="Buscar producto por nombre o código..."
                      value={searchTerm}
                      onChange={(e) => setSearchTerm(e.target.value)}
                      className="w-full pl-10 pr-4 py-2 text-sm border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#219ebc] bg-slate-50 focus:bg-white transition"
                    />
                  </div>
                  <div className="flex-1 overflow-y-auto max-h-[360px] border border-slate-100 rounded-xl">
                    <LookProduct addProduct={addProduct} searchTerm={searchTerm} />
                  </div>
                </div>
              ) : (
                <div className="flex flex-col justify-between gap-4 h-full">
                  <div className="space-y-3">
                    <div>
                      <label className="block text-xs font-bold uppercase text-slate-600 mb-1">
                        Descripción del Servicio o Mantenimiento
                      </label>
                      <textarea
                        placeholder="Ej. Cambio de aceite, revisión eléctrica, ajuste de frenos..."
                        className="w-full border border-slate-300 rounded-xl p-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#219ebc] resize-none"
                        rows="4"
                        value={serviceInput}
                        onChange={(e) => setServiceInput(e.target.value)}
                      />
                    </div>
                    <div>
                      <label className="block text-xs font-bold uppercase text-slate-600 mb-1">
                        Valor de Mano de Obra ($ COP)
                      </label>
                      <input
                        type="number"
                        min="0"
                        placeholder="Ej. 35000"
                        className="w-full border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-[#219ebc]"
                        value={serviceValue}
                        onChange={(e) => setServiceValue(e.target.value)}
                        onKeyDown={(e) => {
                          if (e.key === 'Enter') {
                            e.preventDefault();
                            addService();
                          }
                        }}
                      />
                    </div>
                  </div>
                  <button
                    type="button"
                    className="button-Date py-3 w-full flex justify-center items-center gap-2 rounded-xl text-white text-base font-bold shadow-md"
                    onClick={addService}
                  >
                    <FaPlus />
                    <span>Agregar Servicio a la Factura</span>
                  </button>
                </div>
              )}
            </div>
          </div>

          {/* Columna Derecha: Detalle de Facturación */}
          <div className="lg:col-span-6 border border-slate-200 rounded-xl flex flex-col overflow-hidden bg-white shadow-2xs">
            <div className="bg-[#023047] px-4 py-3 flex items-center justify-between">
              <h2 className="text-white font-bold text-sm uppercase tracking-wider flex items-center gap-2">
                <FaFileInvoiceDollar className="text-[#ffb703]" />
                <span>Detalle de Facturación ({productsDate.length + services.length} ítems)</span>
              </h2>
              {(productsDate.length > 0 || services.length > 0) && (
                <button
                  type="button"
                  onClick={handleClearSale}
                  className="text-xs text-rose-300 hover:text-rose-100 font-semibold transition"
                >
                  Vaciar todo
                </button>
              )}
            </div>

            <div className="flex-1 p-3 overflow-y-auto max-h-[360px]">
              {productsDate.length === 0 && services.length === 0 ? (
                <div className="h-full min-h-[240px] flex flex-col items-center justify-center text-slate-400 text-center p-6">
                  <FaFileInvoiceDollar className="text-5xl mb-3 opacity-30" />
                  <p className="font-semibold text-slate-500">
                    Aún no hay productos ni servicios en la factura
                  </p>
                  <p className="text-xs text-slate-400 mt-1">
                    Selecciona productos del catálogo izquierdo o agrega un servicio de taller
                  </p>
                </div>
              ) : (
                <table className="w-full text-left border-collapse">
                  <thead>
                    <tr className="border-b border-slate-200 text-xs font-bold uppercase text-slate-500 bg-slate-50">
                      <th className="py-2 px-2">Tipo</th>
                      <th className="py-2 px-2">Descripción</th>
                      <th className="py-2 px-2 text-center">Cant.</th>
                      <th className="py-2 px-2 text-right">Subtotal</th>
                      <th className="py-2 px-2 text-center w-12"></th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100 text-sm">
                    {productsDate.map((product, index) => (
                      <tr key={`prod-${product.id}-${index}`} className="hover:bg-slate-50">
                        <td className="py-2.5 px-2">
                          <span className="px-2 py-0.5 rounded-full text-[11px] font-bold bg-sky-100 text-sky-800">
                            Producto
                          </span>
                        </td>
                        <td className="py-2.5 px-2 font-semibold text-slate-800">
                          <div>{product.name}</div>
                          <div className="text-xs text-slate-400">
                            Unit: $ {formatPrice(product.price)}
                          </div>
                        </td>
                        <td className="py-2.5 px-2">
                          <div className="flex items-center justify-center gap-1.5">
                            <button
                              type="button"
                              onClick={() => updateProductQty(index, -1)}
                              className="w-6 h-6 rounded-md bg-slate-100 hover:bg-slate-200 text-slate-700 flex items-center justify-center text-xs"
                            >
                              <FaMinus />
                            </button>
                            <span className="w-7 text-center font-bold text-slate-800">
                              {product.und}
                            </span>
                            <button
                              type="button"
                              onClick={() => updateProductQty(index, 1)}
                              className="w-6 h-6 rounded-md bg-slate-100 hover:bg-slate-200 text-slate-700 flex items-center justify-center text-xs"
                            >
                              <FaPlus />
                            </button>
                          </div>
                        </td>
                        <td className="py-2.5 px-2 text-right font-bold text-[#023047] whitespace-nowrap">
                          $ {formatPrice(product.total)}
                        </td>
                        <td className="py-2.5 px-2 text-center">
                          <button
                            type="button"
                            onClick={() => removeProduct(index)}
                            className="p-1.5 rounded-lg text-rose-500 hover:bg-rose-50 transition"
                            title="Eliminar producto"
                          >
                            <FaTrashAlt />
                          </button>
                        </td>
                      </tr>
                    ))}

                    {services.map((service, index) => (
                      <tr key={`srv-${index}`} className="hover:bg-slate-50">
                        <td className="py-2.5 px-2">
                          <span className="px-2 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800">
                            Servicio
                          </span>
                        </td>
                        <td className="py-2.5 px-2 font-semibold text-slate-800">
                          {service.date}
                        </td>
                        <td className="py-2.5 px-2 text-center text-slate-500 font-medium">
                          1
                        </td>
                        <td className="py-2.5 px-2 text-right">
                          <div className="inline-flex items-center justify-end gap-1">
                            <span className="text-slate-400 font-bold">$</span>
                            <input
                              type="number"
                              min="0"
                              value={service.price === 0 ? '' : service.price}
                              placeholder="Valor..."
                              onChange={(e) => handlePriceChange(index, e.target.value)}
                              className="w-28 px-2 py-1 text-right border border-slate-300 rounded-lg font-bold text-[#023047] focus:outline-none focus:ring-2 focus:ring-[#219ebc]"
                            />
                          </div>
                        </td>
                        <td className="py-2.5 px-2 text-center">
                          <button
                            type="button"
                            onClick={() => removeService(index)}
                            className="p-1.5 rounded-lg text-rose-500 hover:bg-rose-50 transition"
                            title="Eliminar servicio"
                          >
                            <FaTrashAlt />
                          </button>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              )}
            </div>

            {/* Subtotales dentro del panel derecho */}
            <div className="bg-slate-50 border-t border-slate-200 px-4 py-2.5 flex flex-wrap items-center justify-between text-xs sm:text-sm text-slate-600">
              <span>
                Subtotal Productos: <strong className="text-slate-800">$ {formatPrice(subtotalProducts)}</strong>
              </span>
              <span>
                Subtotal Servicios: <strong className="text-slate-800">$ {formatPrice(subtotalServices)}</strong>
              </span>
            </div>
          </div>
        </section>

        {/* Sección 3: Barra Inferior de Total y Botón Facturar */}
        <section className="bg-[#023047] text-white rounded-xl p-4 flex flex-col sm:flex-row items-center justify-between gap-4 shadow-md">
          <div className="flex items-baseline gap-3">
            <span className="text-sm sm:text-base font-bold uppercase tracking-wider text-sky-200">
              Total a Pagar:
            </span>
            <span className="text-3xl sm:text-4xl font-black text-[#ffb703]">
              $ {formatPrice(total)}
            </span>
          </div>

          <button
            type="button"
            disabled={isSubmitting}
            onClick={handleInvoice}
            className="w-full sm:w-auto min-w-[260px] py-3.5 px-8 rounded-xl bg-gradient-to-r from-[#ffb703] to-[#fb8500] hover:from-[#fb8500] hover:to-[#ffb703] text-[#023047] font-black text-lg shadow-lg transition disabled:opacity-50 cursor-pointer"
          >
            {isSubmitting ? 'Procesando Factura...' : 'Facturar e Imprimir'}
          </button>

          {PrintId && invoices && <FactuPrint key={PrintId} id={invoices} />}
          <Ready show={showModal} onClose={handleCloseModal} Date="FACTURADO" />
        </section>
      </div>
    </div>
  );
};

export default Sale;