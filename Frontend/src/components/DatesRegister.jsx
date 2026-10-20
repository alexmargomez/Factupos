import React, { useEffect, useState } from 'react';
import { FaUser, FaMotorcycle, FaEraser, FaTachometerAlt, FaExclamationTriangle } from 'react-icons/fa';

const DatesRegister = ({
  pent,
  token,
  API_URL,
  setClientData,
  setVehicleData,
  setClientValues,
  setVehicleValues,
  setServices,
  setScheduleMeta,
}) => {
  const [localClient, setLocalClient] = useState({
    id: '',
    name: '',
    phone: '',
    email: '',
  });
  const [localVehicle, setLocalVehicle] = useState({
    id: '',
    plate: '',
    make: '',
    model: '',
    current_mileage: '',
    next_oil_change_km: '',
    soat_due_date: '',
    tecno_due_date: '',
  });
  const [allClients, setAllClients] = useState([]);
  const [allVehicles, setAllVehicles] = useState([]);

  useEffect(() => {
    const fetchCustomersAndVehicles = async () => {
      try {
        const [custRes, vehRes] = await Promise.all([
          fetch(`${API_URL}/customers`, {
            headers: { Authorization: `Bearer ${token}` },
          }),
          fetch(`${API_URL}/vehicles`, {
            headers: { Authorization: `Bearer ${token}` },
          }),
        ]);

        if (custRes.ok) {
          const custList = await custRes.json();
          const safeCust = Array.isArray(custList) ? custList : [];
          setAllClients(safeCust);
          setClientData(safeCust);
        }

        if (vehRes.ok) {
          const vehList = await vehRes.json();
          const safeVeh = Array.isArray(vehList) ? vehList : [];
          setAllVehicles(safeVeh);
          setVehicleData(safeVeh);
        }
      } catch (error) {
        console.error('Error cargando clientes/vehículos:', error);
      }
    };

    if (API_URL && token) {
      fetchCustomersAndVehicles();
    }
  }, [token, API_URL, setClientData, setVehicleData]);

  const mapVehicleFields = (v) => ({
    id: v.id || '',
    plate: v.plate || '',
    make: v.make || '',
    model: v.model || '',
    current_mileage: v.current_mileage != null ? String(v.current_mileage) : '',
    next_oil_change_km: v.next_oil_change_km != null ? String(v.next_oil_change_km) : '',
    soat_due_date: v.soat_due_date ? String(v.soat_due_date).slice(0, 10) : '',
    tecno_due_date: v.tecno_due_date ? String(v.tecno_due_date).slice(0, 10) : '',
  });

  // Cuando cambia el cliente seleccionado (tiene id), buscar sus vehículos y citas pendientes
  useEffect(() => {
    const loadClientSchedule = async () => {
      if (!localClient.id) return;

      const clientVehicles = allVehicles.filter(
        (v) => Number(v.customer_id) === Number(localClient.id)
      );

      if (clientVehicles.length > 0 && !localVehicle.id) {
        const nextVeh = mapVehicleFields(clientVehicles[0]);
        setLocalVehicle(nextVeh);
        setVehicleValues(nextVeh);
      }

      try {
        const response = await fetch(`${API_URL}/schedules/${localClient.id}`, {
          headers: { Authorization: `Bearer ${token}` },
        });
        if (!response.ok) return;
        const result = await response.json();

        if (Array.isArray(result) && result.length > 0) {
          const schedulePendiente = result.find((s) => s.state === 'Pendiente');
          if (schedulePendiente) {
            if (pent) {
              pent(schedulePendiente.worker_id);
            }
            if (setScheduleMeta) {
              setScheduleMeta({
                workshop_status: schedulePendiente.workshop_status || 'Recibido',
                mileage:
                  schedulePendiente.mileage != null
                    ? String(schedulePendiente.mileage)
                    : '',
                notes: schedulePendiente.notes || '',
              });
            }
            const veh = allVehicles.find(
              (v) => Number(v.id) === Number(schedulePendiente.vehicle_id)
            );
            if (veh) {
              const nextVeh = mapVehicleFields(veh);
              if (schedulePendiente.mileage != null) {
                nextVeh.current_mileage = String(schedulePendiente.mileage);
              }
              setLocalVehicle(nextVeh);
              setVehicleValues(nextVeh);
            }
            try {
              let serviciosArray = schedulePendiente.servicios;
              while (typeof serviciosArray === 'string') {
                serviciosArray = JSON.parse(serviciosArray);
              }
              if (Array.isArray(serviciosArray)) {
                setServices(serviciosArray);
              }
            } catch (e) {
              console.error('Error al parsear servicios agendados:', e);
            }
          }
        }
      } catch (err) {
        console.error('Error consultando agendamientos del cliente:', err);
      }
    };

    loadClientSchedule();
  }, [localClient.id, allVehicles, API_URL, token, pent, setVehicleValues, setServices, setScheduleMeta]);

  const handleClientFieldChange = (field, value) => {
    let nextClient = { ...localClient, [field]: value };

    if (field === 'name') {
      const matched = allClients.find(
        (c) => (c.name || '').toLowerCase() === value.trim().toLowerCase()
      );
      if (matched) {
        nextClient = {
          id: matched.id,
          name: matched.name || '',
          phone: matched.phone ? String(matched.phone) : '',
          email: matched.email || '',
        };
      } else {
        nextClient.id = '';
      }
    }

    setLocalClient(nextClient);
    setClientValues(nextClient);
  };

  const handleVehicleFieldChange = (field, value) => {
    const formattedValue = field === 'plate' ? value.toUpperCase() : value;
    let nextVehicle = { ...localVehicle, [field]: formattedValue };

    if (field === 'plate') {
      const matched = allVehicles.find(
        (v) => (v.plate || '').toUpperCase() === formattedValue.trim().toUpperCase()
      );
      if (matched) {
        nextVehicle = mapVehicleFields(matched);
        if (!localClient.id && matched.customer_id) {
          const owner = allClients.find(
            (c) => Number(c.id) === Number(matched.customer_id)
          );
          if (owner) {
            const ownerData = {
              id: owner.id,
              name: owner.name || '',
              phone: owner.phone ? String(owner.phone) : '',
              email: owner.email || '',
            };
            setLocalClient(ownerData);
            setClientValues(ownerData);
          }
        }
      } else {
        nextVehicle.id = '';
      }
    }

    setLocalVehicle(nextVehicle);
    setVehicleValues(nextVehicle);
  };

  const suggestNextOilChange = () => {
    const currentKm = Number(localVehicle.current_mileage);
    if (!isNaN(currentKm) && currentKm > 0) {
      const suggested = String(currentKm + 3000);
      const nextVeh = { ...localVehicle, next_oil_change_km: suggested };
      setLocalVehicle(nextVeh);
      setVehicleValues(nextVeh);
    }
  };

  const handleClear = () => {
    const emptyClient = { id: '', name: '', phone: '', email: '' };
    const emptyVehicle = {
      id: '',
      plate: '',
      make: '',
      model: '',
      current_mileage: '',
      next_oil_change_km: '',
      soat_due_date: '',
      tecno_due_date: '',
    };
    setLocalClient(emptyClient);
    setLocalVehicle(emptyVehicle);
    setClientValues(emptyClient);
    setVehicleValues(emptyVehicle);
    if (pent) pent('');
    if (setServices) setServices([]);
    if (setScheduleMeta) {
      setScheduleMeta({ workshop_status: 'Recibido', mileage: '', notes: '' });
    }
  };

  const filteredVehicles = localClient.id
    ? allVehicles.filter((v) => Number(v.customer_id) === Number(localClient.id))
    : allVehicles;

  // Alertas preventivas del vehículo seleccionado
  const getVehicleAlerts = () => {
    const alerts = [];
    const curKm = Number(localVehicle.current_mileage);
    const nextKm = Number(localVehicle.next_oil_change_km);
    if (curKm > 0 && nextKm > 0) {
      if (curKm >= nextKm) {
        alerts.push(`Cambio de aceite vencido (${curKm.toLocaleString('es-CO')} / ${nextKm.toLocaleString('es-CO')} km)`);
      } else if (nextKm - curKm <= 300) {
        alerts.push(`Próximo cambio de aceite en ${nextKm - curKm} km`);
      }
    }
    const today = new Date().toISOString().slice(0, 10);
    if (localVehicle.soat_due_date && localVehicle.soat_due_date <= today) {
      alerts.push(`SOAT vencido (${localVehicle.soat_due_date})`);
    }
    if (localVehicle.tecno_due_date && localVehicle.tecno_due_date <= today) {
      alerts.push(`Tecnomecánica vencida (${localVehicle.tecno_due_date})`);
    }
    return alerts;
  };

  const vehicleAlerts = getVehicleAlerts();

  return (
    <div className="bg-slate-50 border border-slate-200 rounded-xl p-3.5">
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
        {/* Datos del Cliente */}
        <div className="bg-white p-3.5 rounded-lg border border-slate-200 shadow-2xs flex flex-col justify-between">
          <div>
            <div className="flex items-center justify-between mb-2.5">
              <div className="flex items-center gap-2 text-[#023047] font-bold text-sm uppercase tracking-wide">
                <FaUser className="text-[#219ebc]" />
                <span>Datos del Cliente</span>
                {localClient.id && (
                  <span className="text-[11px] bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded-full font-semibold">
                    Registrado #{localClient.id}
                  </span>
                )}
              </div>
              {(localClient.name || localVehicle.plate) && (
                <button
                  type="button"
                  onClick={handleClear}
                  className="text-xs text-slate-500 hover:text-rose-600 flex items-center gap-1 font-medium transition"
                  title="Limpiar campos de cliente y vehículo"
                >
                  <FaEraser /> Limpiar
                </button>
              )}
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
              <div>
                <label className="block text-xs font-semibold text-slate-600 mb-1">
                  Nombre Completo *
                </label>
                <input
                  type="text"
                  placeholder="Buscar o escribir nombre"
                  list="datalist-client-name"
                  value={localClient.name}
                  onChange={(e) => handleClientFieldChange('name', e.target.value)}
                  className="w-full px-2.5 py-1.5 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#219ebc] bg-white text-slate-800"
                />
                <datalist id="datalist-client-name">
                  {allClients.map((c) => (
                    <option key={c.id} value={c.name} />
                  ))}
                </datalist>
              </div>

              <div>
                <label className="block text-xs font-semibold text-slate-600 mb-1">
                  Teléfono / WhatsApp
                </label>
                <input
                  type="text"
                  placeholder="Ej. 3101234567"
                  value={localClient.phone}
                  onChange={(e) => handleClientFieldChange('phone', e.target.value)}
                  className="w-full px-2.5 py-1.5 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#219ebc] bg-white text-slate-800"
                />
              </div>

              <div>
                <label className="block text-xs font-semibold text-slate-600 mb-1">
                  Correo Electrónico
                </label>
                <input
                  type="email"
                  placeholder="cliente@correo.com"
                  value={localClient.email}
                  onChange={(e) => handleClientFieldChange('email', e.target.value)}
                  className="w-full px-2.5 py-1.5 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#219ebc] bg-white text-slate-800"
                />
              </div>
            </div>
          </div>

          {vehicleAlerts.length > 0 && (
            <div className="mt-3 p-2.5 rounded-lg bg-amber-50 border border-amber-200 flex items-start gap-2 text-xs text-amber-900">
              <FaExclamationTriangle className="text-amber-600 mt-0.5 shrink-0" />
              <div>
                <span className="font-bold">Alertas del Vehículo: </span>
                {vehicleAlerts.join(' • ')}
              </div>
            </div>
          )}
        </div>

        {/* Datos del Vehículo */}
        <div className="bg-white p-3.5 rounded-lg border border-slate-200 shadow-2xs space-y-2.5">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-2 text-[#023047] font-bold text-sm uppercase tracking-wide">
              <FaMotorcycle className="text-[#fb8500] text-lg" />
              <span>Datos del Vehículo y Control Técnico</span>
              {localVehicle.id && (
                <span className="text-[11px] bg-sky-100 text-sky-800 px-2 py-0.5 rounded-full font-semibold">
                  Placa registrada
                </span>
              )}
            </div>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
            <div>
              <label className="block text-xs font-semibold text-slate-600 mb-1">
                Placa
              </label>
              <input
                type="text"
                placeholder="Ej. ABC12D"
                list="datalist-vehicle-plate"
                value={localVehicle.plate}
                onChange={(e) => handleVehicleFieldChange('plate', e.target.value)}
                className="w-full px-2.5 py-1.5 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#219ebc] bg-white text-slate-800 uppercase font-semibold"
              />
              <datalist id="datalist-vehicle-plate">
                {filteredVehicles.map((v) => (
                  <option key={v.id} value={v.plate} />
                ))}
              </datalist>
            </div>

            <div>
              <label className="block text-xs font-semibold text-slate-600 mb-1">
                Marca
              </label>
              <input
                type="text"
                placeholder="Ej. Yamaha, Honda"
                value={localVehicle.make}
                onChange={(e) => handleVehicleFieldChange('make', e.target.value)}
                className="w-full px-2.5 py-1.5 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#219ebc] bg-white text-slate-800"
              />
            </div>

            <div>
              <label className="block text-xs font-semibold text-slate-600 mb-1">
                Modelo / Línea
              </label>
              <input
                type="text"
                placeholder="Ej. FZ 2.0 / 2023"
                value={localVehicle.model}
                onChange={(e) => handleVehicleFieldChange('model', e.target.value)}
                className="w-full px-2.5 py-1.5 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#219ebc] bg-white text-slate-800"
              />
            </div>
          </div>

          {/* Campos técnicos: Kilometraje, Próximo Cambio Aceite, SOAT, Tecnomecánica */}
          <div className="grid grid-cols-2 sm:grid-cols-4 gap-2 pt-1 border-t border-slate-100">
            <div>
              <label className="block text-[11px] font-semibold text-slate-500 mb-1 flex items-center gap-1">
                <FaTachometerAlt className="text-[#219ebc]" /> Km Actual
              </label>
              <input
                type="number"
                min="0"
                placeholder="Ej. 24500"
                value={localVehicle.current_mileage}
                onChange={(e) => handleVehicleFieldChange('current_mileage', e.target.value)}
                className="w-full px-2 py-1 text-xs border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#219ebc] bg-white text-slate-800"
              />
            </div>

            <div>
              <div className="flex items-center justify-between mb-1">
                <label className="block text-[11px] font-semibold text-slate-500">
                  Próx. Aceite (Km)
                </label>
                {localVehicle.current_mileage && (
                  <button
                    type="button"
                    onClick={suggestNextOilChange}
                    className="text-[10px] font-bold text-[#219ebc] hover:underline"
                    title="Sugerir +3.000 km"
                  >
                    +3k km
                  </button>
                )}
              </div>
              <input
                type="number"
                min="0"
                placeholder="Ej. 27500"
                value={localVehicle.next_oil_change_km}
                onChange={(e) => handleVehicleFieldChange('next_oil_change_km', e.target.value)}
                className="w-full px-2 py-1 text-xs border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#219ebc] bg-white text-slate-800"
              />
            </div>

            <div>
              <label className="block text-[11px] font-semibold text-slate-500 mb-1">
                Vence SOAT
              </label>
              <input
                type="date"
                value={localVehicle.soat_due_date}
                onChange={(e) => handleVehicleFieldChange('soat_due_date', e.target.value)}
                className="w-full px-2 py-1 text-xs border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#219ebc] bg-white text-slate-800"
              />
            </div>

            <div>
              <label className="block text-[11px] font-semibold text-slate-500 mb-1">
                Vence Tecno
              </label>
              <input
                type="date"
                value={localVehicle.tecno_due_date}
                onChange={(e) => handleVehicleFieldChange('tecno_due_date', e.target.value)}
                className="w-full px-2 py-1 text-xs border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#219ebc] bg-white text-slate-800"
              />
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default DatesRegister;