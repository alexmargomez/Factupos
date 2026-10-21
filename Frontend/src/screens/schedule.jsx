import React, { useState, useEffect, useCallback } from 'react';
import { useNavigate } from 'react-router-dom';
import DateTimeDisplay from '../components/DateTimeDisplay';
import DatesRegister from '../components/DatesRegister';
import Ready from '../components/Ready';
import DatesWorker from '../services/DatesWorker';
import { FaBookBookmark } from 'react-icons/fa6';
import {
  FaPlus,
  FaTrashAlt,
  FaUserTie,
  FaTools,
  FaWhatsapp,
  FaClipboardList,
  FaColumns,
  FaCashRegister,
  FaTachometerAlt,
  FaStickyNote,
} from 'react-icons/fa';

const WORKSHOP_STATUSES = [
  {
    key: 'Recibido',
    label: 'Recibido',
    badge: 'bg-slate-100 text-slate-800 border-slate-300',
    header: 'bg-slate-700 text-white',
  },
  {
    key: 'En Diagnóstico',
    label: 'En Diagnóstico',
    badge: 'bg-sky-100 text-sky-800 border-sky-300',
    header: 'bg-sky-700 text-white',
  },
  {
    key: 'Esperando Repuesto',
    label: 'Esperando Repuesto',
    badge: 'bg-amber-100 text-amber-900 border-amber-300',
    header: 'bg-amber-600 text-white',
  },
  {
    key: 'En Reparación',
    label: 'En Reparación',
    badge: 'bg-indigo-100 text-indigo-800 border-indigo-300',
    header: 'bg-indigo-700 text-white',
  },
  {
    key: 'Listo para Entrega',
    label: 'Listo para Entrega',
    badge: 'bg-emerald-100 text-emerald-800 border-emerald-300',
    header: 'bg-emerald-700 text-white',
  },
];

const Schedule = () => {
  const API_URL = import.meta.env.VITE_API_URL;
  const token = localStorage.getItem('authToken');
  const navigate = useNavigate();

  const [activeTab, setActiveTab] = useState('form'); // 'form' | 'kanban'
  const [showModal, setShowModal] = useState(false);
  const [worker, setWorker] = useState([]);
  const [selectedWorker, setSelectedWorker] = useState('');
  const [services, setServices] = useState([]);
  const [serviceInput, setServiceInput] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [formError, setFormError] = useState('');

  const [scheduleMeta, setScheduleMeta] = useState({
    workshop_status: 'Recibido',
    mileage: '',
    notes: '',
  });

  const [clientValues, setClientValues] = useState({
    name: '',
    id: '',
    phone: '',
    email: '',
  });
  const [vehicleValues, setVehicleValues] = useState({
    id: '',
    plate: '',
    make: '',
    model: '',
    current_mileage: '',
    next_oil_change_km: '',
    soat_due_date: '',
    tecno_due_date: '',
  });
  const [clientData, setClientData] = useState([]);
  const [vehicleData, setVehicleData] = useState([]);

  // Estado para el Tablero Kanban
  const [activeOrders, setActiveOrders] = useState([]);
  const [loadingKanban, setLoadingKanban] = useState(false);

  const parseServicios = (serviciosRaw) => {
    if (Array.isArray(serviciosRaw)) return serviciosRaw;
    if (typeof serviciosRaw === 'string') {
      try {
        const parsed = JSON.parse(serviciosRaw);
        if (Array.isArray(parsed)) return parsed;
        if (typeof parsed === 'string') {
          const parsedTwice = JSON.parse(parsed);
          return Array.isArray(parsedTwice) ? parsedTwice : [parsedTwice];
        }
      } catch {
        return serviciosRaw ? [serviciosRaw] : [];
      }
    }
    return [];
  };

  const fetchActiveOrders = useCallback(async () => {
    if (!API_URL || !token) return;
    setLoadingKanban(true);
    try {
      const res = await fetch(`${API_URL}/schedules`, {
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: 'application/json',
        },
      });
      if (res.ok) {
        const data = await res.json();
        const pending = (Array.isArray(data) ? data : [])
          .filter((item) => item.state === 'Pendiente')
          .map((item) => ({
            ...item,
            workshop_status: item.workshop_status || 'Recibido',
            serviciosList: parseServicios(item.servicios),
          }));
        setActiveOrders(pending);
      }
    } catch (err) {
      console.error('Error cargando órdenes de taller:', err);
    } finally {
      setLoadingKanban(false);
    }
  }, [API_URL, token]);

  useEffect(() => {
    fetchActiveOrders();
  }, [fetchActiveOrders]);

  const addService = () => {
    if (serviceInput.trim() !== '') {
      setFormError('');
      setServices((prevServices) => [...prevServices, serviceInput.trim()]);
      setServiceInput('');
    }
  };

  const removeService = (index) => {
    setServices((prevServices) => prevServices.filter((_, i) => i !== index));
  };

  const handleSchedule = async () => {
    setFormError('');

    if (!clientValues.name?.trim()) {
      setFormError('Por favor ingresa el nombre del cliente.');
      return;
    }
    if (!vehicleValues.plate?.trim()) {
      setFormError('Por favor ingresa la placa del vehículo.');
      return;
    }
    if (!selectedWorker) {
      setFormError('Por favor selecciona el técnico/empleado encargado.');
      return;
    }
    if (!Array.isArray(services) || services.length === 0) {
      setFormError('Agrega al menos un servicio a realizar en el agendamiento.');
      return;
    }

    setIsSubmitting(true);

    try {
      let customerId = clientValues.id;
      let vehicleId = vehicleValues.id;

      const existingClient = customerId
        ? clientData.find((c) => Number(c.id) === Number(customerId))
        : clientData.find(
            (c) =>
              (c.name || '').trim().toLowerCase() ===
              clientValues.name.trim().toLowerCase()
          );

      if (existingClient) {
        customerId = existingClient.id;
      } else {
        const clienteRes = await fetch(`${API_URL}/customers/`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            Authorization: `Bearer ${token}`,
          },
          body: JSON.stringify({
            name: clientValues.name.trim(),
            phone: clientValues.phone?.trim() || null,
            email: clientValues.email?.trim() || null,
          }),
        });
        if (!clienteRes.ok) {
          throw new Error('Error creando el cliente.');
        }
        const createdCustomer = await clienteRes.json();
        customerId = createdCustomer.id;
      }

      const existingVehicle = vehicleId
        ? vehicleData.find((v) => Number(v.id) === Number(vehicleId))
        : vehicleData.find(
            (v) =>
              (v.plate || '').trim().toUpperCase() ===
              vehicleValues.plate.trim().toUpperCase()
          );

      const parsedMileage =
        vehicleValues.current_mileage !== '' && vehicleValues.current_mileage != null
          ? Number(vehicleValues.current_mileage)
          : scheduleMeta.mileage !== ''
          ? Number(scheduleMeta.mileage)
          : null;

      const vehiclePayload = {
        plate: vehicleValues.plate.trim().toUpperCase(),
        make: vehicleValues.make?.trim() || 'General',
        model: vehicleValues.model?.trim() || 'General',
        customer_id: customerId,
        current_mileage: parsedMileage,
        next_oil_change_km:
          vehicleValues.next_oil_change_km !== '' && vehicleValues.next_oil_change_km != null
            ? Number(vehicleValues.next_oil_change_km)
            : null,
        soat_due_date: vehicleValues.soat_due_date || null,
        tecno_due_date: vehicleValues.tecno_due_date || null,
      };

      if (existingVehicle) {
        vehicleId = existingVehicle.id;
        await fetch(`${API_URL}/vehicles/${vehicleId}`, {
          method: 'PUT',
          headers: {
            'Content-Type': 'application/json',
            Authorization: `Bearer ${token}`,
          },
          body: JSON.stringify(vehiclePayload),
        });
      } else {
        const vehicleResponse = await fetch(`${API_URL}/vehicles/`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            Authorization: `Bearer ${token}`,
          },
          body: JSON.stringify(vehiclePayload),
        });

        if (!vehicleResponse.ok) {
          throw new Error('Error registrando el vehículo.');
        }
        const createdVeh = await vehicleResponse.json();
        vehicleId = createdVeh.id;
      }

      const scheduleExists = await fetch(`${API_URL}/schedules/${customerId}`, {
        method: 'GET',
        headers: {
          Authorization: `Bearer ${token}`,
        },
      });
      const response = scheduleExists.ok ? await scheduleExists.json() : [];
      const responsePendiente = Array.isArray(response)
        ? response.find((schedule) => schedule.state === 'Pendiente')
        : null;

      const schedulePayload = {
        vehicle_id: vehicleId,
        customer_id: customerId,
        servicios: services,
        state: 'Pendiente',
        workshop_status: scheduleMeta.workshop_status || 'Recibido',
        mileage: parsedMileage,
        notes: scheduleMeta.notes?.trim() || null,
        worker_id: Number(selectedWorker),
      };

      if (responsePendiente) {
        const updateRes = await fetch(`${API_URL}/schedules/${responsePendiente.id}`, {
          method: 'PUT',
          headers: {
            'Content-Type': 'application/json',
            Authorization: `Bearer ${token}`,
          },
          body: JSON.stringify(schedulePayload),
        });
        if (!updateRes.ok) {
          throw new Error('Error actualizando la orden de trabajo pendiente.');
        }
      } else {
        const scheduleResponse = await fetch(`${API_URL}/schedules/`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            Authorization: `Bearer ${token}`,
          },
          body: JSON.stringify(schedulePayload),
        });

        if (!scheduleResponse.ok) {
          throw new Error('Error creando la orden de trabajo.');
        }
      }

      await fetchActiveOrders();
      setShowModal(true);
    } catch (error) {
      console.error('Error creando el agendamiento:', error);
      setFormError(error.message || 'Ocurrió un error al guardar la orden de trabajo.');
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleStatusChange = async (order, nextStatus) => {
    try {
      const res = await fetch(`${API_URL}/schedules/${order.id}`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          Authorization: `Bearer ${token}`,
        },
        body: JSON.stringify({
          workshop_status: nextStatus,
        }),
      });
      if (res.ok) {
        setActiveOrders((prev) =>
          prev.map((item) =>
            item.id === order.id ? { ...item, workshop_status: nextStatus } : item
          )
        );
      }
    } catch (err) {
      console.error('Error actualizando estado de orden:', err);
    }
  };

  const sendWhatsAppNotification = (order) => {
    const rawPhone = String(order.customer?.phone || '').replace(/\D/g, '');
    if (!rawPhone) {
      window.alert('Este cliente no tiene un número de teléfono/WhatsApp registrado.');
      return;
    }
    const phone = rawPhone.length === 10 ? `57${rawPhone}` : rawPhone;
    const customerName = order.customer?.name || 'Cliente';
    const plate = order.vehicle?.plate || 'su vehículo';
    const status = order.workshop_status || 'Recibido';
    const servicesText =
      order.serviciosList && order.serviciosList.length > 0
        ? order.serviciosList.join(', ')
        : 'Mantenimiento general';

    let message = `Hola *${customerName}*, te escribimos de *FactuPOS Taller*.\n\n`;
    if (status === 'Listo para Entrega') {
      message += `✅ Te informamos que tu vehículo placa *${plate}* ya se encuentra *LISTO PARA ENTREGA*.\n`;
    } else {
      message += `🔧 El estado actual de la orden de tu vehículo placa *${plate}* es: *${status}*.\n`;
    }
    message += `📋 *Trabajos:* ${servicesText}\n`;
    if (order.mileage) {
      message += `🛣️ *Kilometraje registrado:* ${Number(order.mileage).toLocaleString('es-CO')} km\n`;
    }
    message += `\n¡Gracias por confiar en nosotros!`;

    window.open(
      `https://wa.me/${phone}?text=${encodeURIComponent(message)}`,
      '_blank',
      'noopener,noreferrer'
    );
  };

  const handleCloseModal = () => {
    setShowModal(false);
    setActiveTab('kanban');
  };

  DatesWorker(API_URL, token, setWorker);

  return (
    <div className="min-h-screen w-full flex flex-col bg-slate-100 p-4 md:p-6">
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-4">
        <div>
          <h1 className="text-2xl font-extrabold text-[#023047] tracking-tight flex items-center gap-2">
            <FaBookBookmark className="text-[#fb8500]" />
            <span>GESTIÓN Y ORDENES DE TALLER</span>
          </h1>
          <p className="text-sm text-slate-500">
            Recepción de vehículos, control de kilometraje, tablero Kanban en vivo y avisos por WhatsApp
          </p>
        </div>

        <div className="flex items-center gap-2 flex-wrap">
          <div className="bg-white p-1 rounded-xl border border-slate-200 shadow-2xs flex items-center gap-1">
            <button
              type="button"
              onClick={() => setActiveTab('form')}
              className={`px-3.5 py-2 rounded-lg text-xs font-bold flex items-center gap-2 transition ${
                activeTab === 'form'
                  ? 'bg-[#023047] text-white shadow-xs'
                  : 'text-slate-600 hover:bg-slate-100'
              }`}
            >
              <FaClipboardList />
              <span>Nueva Orden</span>
            </button>
            <button
              type="button"
              onClick={() => {
                setActiveTab('kanban');
                fetchActiveOrders();
              }}
              className={`px-3.5 py-2 rounded-lg text-xs font-bold flex items-center gap-2 transition ${
                activeTab === 'kanban'
                  ? 'bg-[#023047] text-white shadow-xs'
                  : 'text-slate-600 hover:bg-slate-100'
              }`}
            >
              <FaColumns />
              <span>Tablero Taller ({activeOrders.length})</span>
            </button>
          </div>
          <DateTimeDisplay />
        </div>
      </div>

      {activeTab === 'form' ? (
        <div
          className={`flex-1 bg-white rounded-2xl shadow-sm border border-slate-200 border-t-4 border-t-[#ffb703] p-5 flex flex-col gap-4 ${
            showModal ? 'animate-border' : ''
          }`}
        >
          {formError && (
            <div className="p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-sm font-semibold">
              {formError}
            </div>
          )}

          {/* Cliente, Vehículo y Empleado */}
          <section className="flex flex-col gap-3">
            <DatesRegister
              token={token}
              API_URL={API_URL}
              pent={setSelectedWorker}
              setClientData={setClientData}
              setVehicleData={setVehicleData}
              setClientValues={setClientValues}
              setVehicleValues={setVehicleValues}
              setServices={setServices}
              setScheduleMeta={setScheduleMeta}
            />

            <div className="grid grid-cols-1 md:grid-cols-3 gap-3 bg-slate-50 border border-slate-200 rounded-xl p-3.5">
              {/* Técnico Encargado */}
              <div>
                <label className="block text-xs font-bold text-[#023047] mb-1 flex items-center gap-1.5">
                  <FaUserTie className="text-[#219ebc]" />
                  <span>Técnico / Mecánico Encargado *</span>
                </label>
                <select
                  name="worker"
                  id="worker"
                  value={selectedWorker}
                  onChange={(e) => setSelectedWorker(e.target.value)}
                  className="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl bg-white text-slate-800 font-semibold focus:outline-none focus:ring-2 focus:ring-[#219ebc]"
                >
                  <option value="">Seleccionar técnico...</option>
                  {worker.map((w) => (
                    <option key={w.id} value={w.id}>
                      {w.name}
                    </option>
                  ))}
                </select>
              </div>

              {/* Estado Inicial en Taller */}
              <div>
                <label className="block text-xs font-bold text-[#023047] mb-1 flex items-center gap-1.5">
                  <FaColumns className="text-[#fb8500]" />
                  <span>Estado en Taller</span>
                </label>
                <select
                  value={scheduleMeta.workshop_status}
                  onChange={(e) =>
                    setScheduleMeta((prev) => ({
                      ...prev,
                      workshop_status: e.target.value,
                    }))
                  }
                  className="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl bg-white text-slate-800 font-semibold focus:outline-none focus:ring-2 focus:ring-[#219ebc]"
                >
                  {WORKSHOP_STATUSES.map((st) => (
                    <option key={st.key} value={st.key}>
                      {st.label}
                    </option>
                  ))}
                </select>
              </div>

              {/* Observaciones de Recepción */}
              <div>
                <label className="block text-xs font-bold text-[#023047] mb-1 flex items-center gap-1.5">
                  <FaStickyNote className="text-amber-500" />
                  <span>Observaciones de Ingreso (Combustible, Rayones, Accesorios)</span>
                </label>
                <input
                  type="text"
                  placeholder="Ej. Deja casco, tanque 1/2, rayón tapa derecha..."
                  value={scheduleMeta.notes}
                  onChange={(e) =>
                    setScheduleMeta((prev) => ({
                      ...prev,
                      notes: e.target.value,
                    }))
                  }
                  className="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl bg-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#219ebc]"
                />
              </div>
            </div>
          </section>

          {/* Ingreso y Lista de Servicios */}
          <section className="grid grid-cols-1 lg:grid-cols-2 gap-4 flex-1 min-h-[300px]">
            <div className="border border-slate-200 rounded-xl overflow-hidden flex flex-col">
              <div className="bg-[#023047] text-white px-4 py-3 font-bold text-sm uppercase tracking-wider flex items-center gap-2">
                <FaTools className="text-[#ffb703]" />
                <span>Nuevo Trabajo / Servicio a Realizar</span>
              </div>
              <div className="p-4 flex-1 flex flex-col justify-between gap-4">
                <textarea
                  placeholder="Describe el trabajo o revisión solicitada por el cliente (presiona Enter para agregar)..."
                  className="w-full flex-1 border border-slate-300 rounded-xl p-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#219ebc] resize-none"
                  rows="5"
                  value={serviceInput}
                  onChange={(e) => setServiceInput(e.target.value)}
                  onKeyDown={(e) => {
                    if (e.key === 'Enter' && !e.shiftKey) {
                      e.preventDefault();
                      addService();
                    }
                  }}
                />
                <button
                  type="button"
                  className="button-Date py-3 w-full flex justify-center items-center gap-2 rounded-xl text-white font-bold shadow-sm"
                  onClick={addService}
                >
                  <FaPlus />
                  <span>Agregar a la Orden</span>
                </button>
              </div>
            </div>

            <div className="border border-slate-200 rounded-xl overflow-hidden flex flex-col">
              <div className="bg-[#023047] text-white px-4 py-3 font-bold text-sm uppercase tracking-wider">
                Servicios Agregados a la Orden ({Array.isArray(services) ? services.length : 0})
              </div>
              <div className="p-4 flex-1 overflow-y-auto max-h-[340px] space-y-2">
                {Array.isArray(services) && services.length > 0 ? (
                  services.map((service, index) => (
                    <div
                      key={index}
                      className="p-3 bg-slate-50 border border-slate-200 rounded-xl flex justify-between items-center gap-3"
                    >
                      <span className="text-sm font-semibold text-slate-800">
                        {typeof service === 'object' ? service.date : service}
                      </span>
                      <button
                        type="button"
                        className="p-2 rounded-lg text-rose-600 hover:bg-rose-50 transition"
                        onClick={() => removeService(index)}
                        title="Quitar servicio"
                      >
                        <FaTrashAlt />
                      </button>
                    </div>
                  ))
                ) : (
                  <div className="h-full flex items-center justify-center text-slate-400 text-sm py-12">
                    No hay servicios agregados aún.
                  </div>
                )}
              </div>
            </div>
          </section>

          {/* Botón Guardar Agendamiento */}
          <section className="pt-2">
            <button
              type="button"
              disabled={isSubmitting}
              className="w-full py-3.5 rounded-xl bg-gradient-to-r from-[#ffb703] to-[#fb8500] hover:from-[#fb8500] hover:to-[#ffb703] text-[#023047] font-black text-lg shadow-md transition disabled:opacity-50"
              onClick={handleSchedule}
            >
              {isSubmitting ? 'Guardando Orden de Trabajo...' : 'Guardar Orden de Trabajo'}
            </button>
            <Ready show={showModal} onClose={handleCloseModal} Date="ORDEN REGISTRADA" />
          </section>
        </div>
      ) : (
        /* TABLERO KANBAN DE TALLER EN VIVO */
        <div className="flex-1 flex flex-col gap-4">
          {loadingKanban ? (
            <div className="bg-white rounded-2xl p-12 text-center text-slate-500 font-medium border border-slate-200">
              Cargando tablero de órdenes en taller...
            </div>
          ) : (
            <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-4 flex-1">
              {WORKSHOP_STATUSES.map((statusCol) => {
                const colOrders = activeOrders.filter(
                  (o) => (o.workshop_status || 'Recibido') === statusCol.key
                );
                return (
                  <div
                    key={statusCol.key}
                    className="bg-slate-200/70 rounded-2xl border border-slate-300/80 flex flex-col overflow-hidden min-h-[480px]"
                  >
                    <div
                      className={`${statusCol.header} px-3.5 py-2.5 flex items-center justify-between font-bold text-xs uppercase tracking-wider`}
                    >
                      <span>{statusCol.label}</span>
                      <span className="bg-white/20 px-2 py-0.5 rounded-full text-xs">
                        {colOrders.length}
                      </span>
                    </div>

                    <div className="p-3 space-y-3 flex-1 overflow-y-auto">
                      {colOrders.length === 0 ? (
                        <div className="text-center py-10 text-xs text-slate-400 font-medium">
                          Sin vehículos en esta etapa
                        </div>
                      ) : (
                        colOrders.map((order) => (
                          <div
                            key={order.id}
                            className="bg-white rounded-xl p-3.5 shadow-xs border border-slate-200 flex flex-col gap-2.5"
                          >
                            <div className="flex items-center justify-between gap-2">
                              <span className="bg-amber-100 text-amber-900 border border-amber-300 px-2.5 py-0.5 rounded font-mono text-xs font-black">
                                {order.vehicle?.plate || `#${order.vehicle_id}`}
                              </span>
                              <span className="text-[11px] text-slate-400 font-semibold">
                                Orden #{order.id}
                              </span>
                            </div>

                            <div>
                              <p className="text-xs font-bold text-[#023047]">
                                {order.vehicle?.make} {order.vehicle?.model}
                              </p>
                              <p className="text-xs text-slate-600 font-medium">
                                👤 {order.customer?.name || 'Cliente'}
                                {order.customer?.phone ? ` • ${order.customer.phone}` : ''}
                              </p>
                              <p className="text-[11px] text-sky-700 font-semibold mt-0.5">
                                🔧 Técnico: {order.worker?.name || 'Sin asignar'}
                              </p>
                            </div>

                            {(order.mileage || order.vehicle?.current_mileage) && (
                              <div className="flex items-center gap-1 text-[11px] text-slate-600 bg-slate-50 px-2 py-1 rounded border border-slate-200">
                                <FaTachometerAlt className="text-[#219ebc]" />
                                <span>
                                  {Number(
                                    order.mileage || order.vehicle?.current_mileage
                                  ).toLocaleString('es-CO')}{' '}
                                  km
                                </span>
                              </div>
                            )}

                            {order.notes && (
                              <div className="text-[11px] text-amber-900 bg-amber-50/80 border border-amber-200 rounded-lg px-2.5 py-1.5">
                                <span className="font-bold">Nota: </span>
                                {order.notes}
                              </div>
                            )}

                            <div className="flex flex-wrap gap-1">
                              {order.serviciosList?.map((srv, idx) => (
                                <span
                                  key={idx}
                                  className="bg-sky-50 text-[#023047] border border-sky-200 px-2 py-0.5 rounded text-[11px] font-semibold"
                                >
                                  {typeof srv === 'object' ? srv.date : srv}
                                </span>
                              ))}
                            </div>

                            <div className="pt-2 border-t border-slate-100 flex flex-col gap-2">
                              <select
                                value={order.workshop_status || 'Recibido'}
                                onChange={(e) =>
                                  handleStatusChange(order, e.target.value)
                                }
                                className="w-full px-2.5 py-1.5 text-xs font-bold border border-slate-300 rounded-lg bg-slate-50 text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#219ebc]"
                              >
                                {WORKSHOP_STATUSES.map((st) => (
                                  <option key={st.key} value={st.key}>
                                    Mover a: {st.label}
                                  </option>
                                ))}
                              </select>

                              <div className="grid grid-cols-2 gap-1.5">
                                <button
                                  type="button"
                                  onClick={() => sendWhatsAppNotification(order)}
                                  className="px-2.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold flex items-center justify-center gap-1.5 transition"
                                  title="Enviar estado por WhatsApp al cliente"
                                >
                                  <FaWhatsapp className="text-sm" />
                                  <span>WhatsApp</span>
                                </button>

                                <button
                                  type="button"
                                  onClick={() => navigate('/sales')}
                                  className="px-2.5 py-1.5 rounded-lg bg-[#023047] hover:bg-[#034263] text-white text-xs font-bold flex items-center justify-center gap-1.5 transition"
                                  title="Ir a Facturación POS"
                                >
                                  <FaCashRegister />
                                  <span>Facturar</span>
                                </button>
                              </div>
                            </div>
                          </div>
                        ))
                      )}
                    </div>
                  </div>
                );
              })}
            </div>
          )}
        </div>
      )}
    </div>
  );
};

export default Schedule;