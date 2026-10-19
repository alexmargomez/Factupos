import React, { useState } from 'react';
import DateTimeDisplay from '../components/DateTimeDisplay';
import DatesRegister from '../components/DatesRegister';
import Ready from '../components/Ready';
import DatesWorker from '../services/DatesWorker';
import { FaBookBookmark, FaPlus, FaTrashAlt, FaUserTie, FaTools } from 'react-icons/fa';

const Schedule = () => {
  const API_URL = import.meta.env.VITE_API_URL;
  const token = localStorage.getItem('authToken');
  const [showModal, setShowModal] = useState(false);
  const [worker, setWorker] = useState([]);
  const [selectedWorker, setSelectedWorker] = useState('');
  const [services, setServices] = useState([]);
  const [serviceInput, setServiceInput] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [formError, setFormError] = useState('');

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
  });
  const [clientData, setClientData] = useState([]);
  const [vehicleData, setVehicleData] = useState([]);

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
            'Authorization': `Bearer ${token}`,
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
          throw new Error('Error registrando el vehículo.');
        }
        const createdVeh = await vehicleResponse.json();
        vehicleId = createdVeh.id;
      }

      const scheduleExists = await fetch(`${API_URL}/schedules/${customerId}`, {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${token}`,
        },
      });
      const response = scheduleExists.ok ? await scheduleExists.json() : [];
      const responsePendiente = Array.isArray(response)
        ? response.find((schedule) => schedule.state === 'Pendiente')
        : null;

      if (responsePendiente) {
        const updateRes = await fetch(`${API_URL}/schedules/${responsePendiente.id}`, {
          method: 'PUT',
          headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${token}`,
          },
          body: JSON.stringify({
            servicios: services,
            state: 'Pendiente',
            worker_id: Number(selectedWorker),
          }),
        });
        if (!updateRes.ok) {
          throw new Error('Error actualizando el agendamiento pendiente.');
        }
      } else {
        const scheduleResponse = await fetch(`${API_URL}/schedules/`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${token}`,
          },
          body: JSON.stringify({
            vehicle_id: vehicleId,
            customer_id: customerId,
            servicios: services,
            state: 'Pendiente',
            worker_id: Number(selectedWorker),
          }),
        });

        if (!scheduleResponse.ok) {
          throw new Error('Error creando el agendamiento.');
        }
      }

      setShowModal(true);
    } catch (error) {
      console.error('Error creando el agendamiento:', error);
      setFormError(error.message || 'Ocurrió un error al guardar el agendamiento.');
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleCloseModal = () => {
    setShowModal(false);
    window.location.reload();
  };

  DatesWorker(API_URL, token, setWorker);

  return (
    <div className="min-h-screen w-full flex flex-col bg-slate-100 p-4 md:p-6">
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-4">
        <div>
          <h1 className="text-2xl font-extrabold text-[#023047] tracking-tight flex items-center gap-2">
            <FaBookBookmark className="text-[#fb8500]" />
            <span>AGENDAMIENTO DE TALLER</span>
          </h1>
          <p className="text-sm text-slate-500">
            Registra el ingreso de motocicletas, asigna mecánico y lista los trabajos a realizar
          </p>
        </div>
        <DateTimeDisplay />
      </div>

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
          />

          <div className="flex flex-wrap items-center gap-3 bg-slate-50 border border-slate-200 rounded-xl px-4 py-3">
            <div className="flex items-center gap-2 text-sm font-bold text-[#023047]">
              <FaUserTie className="text-[#219ebc]" />
              <span>Técnico / Mecánico Encargado *:</span>
            </div>
            <select
              name="worker"
              id="worker"
              value={selectedWorker}
              onChange={(e) => setSelectedWorker(e.target.value)}
              className="px-3.5 py-2 text-sm border border-slate-300 rounded-xl bg-white text-slate-800 font-semibold focus:outline-none focus:ring-2 focus:ring-[#219ebc] min-w-[240px]"
            >
              <option value="">Seleccionar técnico...</option>
              {worker.map((w) => (
                <option key={w.id} value={w.id}>
                  {w.name}
                </option>
              ))}
            </select>
          </div>
        </section>

        {/* Ingreso y Lista de Servicios */}
        <section className="grid grid-cols-1 lg:grid-cols-2 gap-4 flex-1 min-h-[320px]">
          <div className="border border-slate-200 rounded-xl overflow-hidden flex flex-col">
            <div className="bg-[#023047] text-white px-4 py-3 font-bold text-sm uppercase tracking-wider flex items-center gap-2">
              <FaTools className="text-[#ffb703]" />
              <span>Nuevo Trabajo / Servicio a Realizar</span>
            </div>
            <div className="p-4 flex-1 flex flex-col justify-between gap-4">
              <textarea
                placeholder="Describe el trabajo o revisión solicitada por el cliente..."
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
            {isSubmitting ? 'Guardando Orden...' : 'Guardar Agendamiento'}
          </button>
          <Ready show={showModal} onClose={handleCloseModal} Date="AGENDADO" />
        </section>
      </div>
    </div>
  );
};

export default Schedule;