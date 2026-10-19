import React, { useState, useEffect } from "react";
import Modal from "./Modal";
import { FaEye } from "react-icons/fa";
import { TbHourglassEmpty } from "react-icons/tb";

const ReportCustomer = ({ id, name, phone, email }) => {
  const token = localStorage.getItem("authToken");
  const API_URL = import.meta.env.VITE_API_URL;
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [dateServices, setDateServices] = useState([]);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    if (!isModalOpen || !id) return;

    let isMounted = true;
    const fetchCustomerServices = async () => {
      setLoading(true);
      try {
        const responseService = await fetch(`${API_URL}/services/customer/${id}`, {
          method: "GET",
          headers: {
            Authorization: `Bearer ${token}`,
            Accept: "application/json",
          },
        });
        if (responseService.ok) {
          const resultService = await responseService.json();
          if (isMounted) {
            setDateServices(Array.isArray(resultService) ? resultService : []);
          }
        }
      } catch (error) {
        console.error("Error al consultar servicios del cliente:", error);
      } finally {
        if (isMounted) setLoading(false);
      }
    };

    fetchCustomerServices();
    return () => {
      isMounted = false;
    };
  }, [isModalOpen, token, API_URL, id]);

  const openModal = () => setIsModalOpen(true);
  const closeModal = () => setIsModalOpen(false);

  const formatDateTime = (dateTime) => {
    if (!dateTime) return "N/A";
    const date = new Date(dateTime);
    return isNaN(date) ? "Fecha inválida" : date.toLocaleDateString("es-CO");
  };

  const formatPrice = (price) => {
    const num = Number(price || 0);
    return isNaN(num) ? "0" : num.toLocaleString("es-CO");
  };

  const totalGastado = dateServices.reduce(
    (sum, s) => sum + Number(s.price || 0),
    0
  );

  return (
    <div>
      <button
        type="button"
        onClick={openModal}
        title="Ver historial de servicios del cliente"
        className="p-2 rounded-lg bg-sky-50 text-[#023047] hover:bg-[#023047] hover:text-white transition-colors cursor-pointer"
      >
        <FaEye className="text-base" />
      </button>

      <Modal show={isModalOpen} onClose={closeModal}>
        <div className="flex flex-col w-full text-left">
          {/* Encabezado Cliente */}
          <div className="bg-[#023047] text-white p-5 rounded-t-xl grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
            <div>
              <span className="text-xs uppercase tracking-wider text-sky-300 block">
                ID Cliente
              </span>
              <p className="font-bold text-base">#{id}</p>
            </div>
            <div>
              <span className="text-xs uppercase tracking-wider text-sky-300 block">
                Nombre
              </span>
              <p className="font-bold text-base truncate">{name || "N/A"}</p>
            </div>
            <div>
              <span className="text-xs uppercase tracking-wider text-sky-300 block">
                Teléfono
              </span>
              <p className="font-semibold text-sm">{phone || "No registrado"}</p>
            </div>
            <div>
              <span className="text-xs uppercase tracking-wider text-sky-300 block">
                Email
              </span>
              <p className="font-semibold text-sm truncate">
                {email || "No registrado"}
              </p>
            </div>
          </div>

          {/* Cuerpo */}
          <div className="p-5 bg-white max-h-[60vh] overflow-y-auto">
            {loading ? (
              <div className="py-12 text-center text-slate-500 font-medium">
                Cargando historial del cliente...
              </div>
            ) : dateServices.length > 0 ? (
              <div className="overflow-x-auto">
                <table className="w-full text-sm text-left border-collapse">
                  <thead>
                    <tr className="border-b-2 border-slate-200 text-slate-600 uppercase text-xs">
                      <th className="py-3 px-3">Vehículo (Placa)</th>
                      <th className="py-3 px-3">Servicio Realizado</th>
                      <th className="py-3 px-3 text-right">Valor Cobrado</th>
                      <th className="py-3 px-3 text-right">Fecha</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100">
                    {dateServices.map((service) => (
                      <tr key={service.id} className="hover:bg-slate-50">
                        <td className="py-3 px-3 font-semibold text-[#023047]">
                          {service.vehicle?.plate ? (
                            <span className="inline-block bg-amber-100 text-amber-900 px-2 py-0.5 rounded font-mono text-xs font-bold border border-amber-300">
                              {service.vehicle.plate}
                            </span>
                          ) : service.vehicle_id ? (
                            `Vehículo #${service.vehicle_id}`
                          ) : (
                            "Mostrador"
                          )}
                        </td>
                        <td className="py-3 px-3 text-slate-700">
                          {service.date}
                        </td>
                        <td className="py-3 px-3 text-right font-semibold text-slate-800">
                          $ {formatPrice(service.price)}
                        </td>
                        <td className="py-3 px-3 text-right text-slate-500">
                          {formatDateTime(service.created_at)}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            ) : (
              <div className="flex flex-col py-12 justify-center items-center text-slate-400 space-y-3">
                <TbHourglassEmpty className="text-5xl text-slate-300" />
                <p className="text-base font-medium text-slate-500">
                  No hay servicios registrados para este cliente
                </p>
              </div>
            )}
          </div>

          {/* Pie */}
          <div className="bg-slate-50 px-5 py-3 rounded-b-xl border-t border-slate-200 flex items-center justify-between">
            <div className="text-sm text-slate-600">
              Total acumulado en servicios:{" "}
              <span className="font-bold text-[#023047]">
                $ {formatPrice(totalGastado)}
              </span>
            </div>
            <button type="button" className="rojo" onClick={closeModal}>
              Cerrar
            </button>
          </div>
        </div>
      </Modal>
    </div>
  );
};

export default ReportCustomer;