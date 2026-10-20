import React, { useState, useEffect, useCallback } from "react";
import FactuView from "./FactuView";
import Modalmini from "./Modalmini";
import ReportCustomer from "./ReportCustomer";
import ReportVehicle from "./ReportVehicle";
import { FaEdit, FaTrash, FaArrowUp, FaArrowDown, FaWhatsapp } from "react-icons/fa";

const WORKSHOP_STATUS_OPTIONS = [
  "Recibido",
  "En Diagnóstico",
  "Esperando Repuesto",
  "En Reparación",
  "Listo para Entrega",
];

const LookDetail = ({ selectedOption, searchTerm, setNumber }) => {
  const API_URL = import.meta.env.VITE_API_URL;
  const [data, setData] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const token = localStorage.getItem("authToken");
  const [isModalOpen, setIsModalOpen] = useState(false);

  const [productUpdate, setProductUpdate] = useState({
    id: "",
    name: "",
    price: "",
    stock: "",
  });
  const [customerUpdate, setCustomerUpdate] = useState({
    id: "",
    name: "",
    phone: "",
    email: "",
  });
  const [vehicleUpdate, setVehicleUpdate] = useState({
    id: "",
    plate: "",
    make: "",
    model: "",
    customer_id: "",
    current_mileage: "",
    next_oil_change_km: "",
    soat_due_date: "",
    tecno_due_date: "",
  });

  const parseServicios = (serviciosRaw) => {
    if (Array.isArray(serviciosRaw)) return serviciosRaw;
    if (typeof serviciosRaw === "string") {
      try {
        const parsed = JSON.parse(serviciosRaw);
        if (Array.isArray(parsed)) return parsed;
        if (typeof parsed === "string") {
          const parsedTwice = JSON.parse(parsed);
          return Array.isArray(parsedTwice) ? parsedTwice : [parsedTwice];
        }
      } catch {
        return serviciosRaw ? [serviciosRaw] : [];
      }
    }
    return [];
  };

  const fetchData = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      let endpoint = "";

      switch (selectedOption) {
        case "Productos":
          endpoint = "products";
          setNumber(0);
          break;
        case "Clientes":
          endpoint = "customers";
          setNumber(1);
          break;
        case "Vehiculos":
          endpoint = "vehicles";
          setNumber(2);
          break;
        case "Ventas":
          endpoint = "sales";
          setNumber(3);
          break;
        case "Pendientes":
          endpoint = "schedules";
          setNumber(4);
          break;
        case "Movimientos":
          endpoint = "inventory-movements";
          setNumber(5);
          break;
        default:
          throw new Error(`Opción no válida: ${selectedOption}`);
      }

      const response = await fetch(`${API_URL}/${endpoint}`, {
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: "application/json",
        },
      });

      if (!response.ok) {
        throw new Error(
          `Error al obtener ${selectedOption.toLowerCase()} (${response.status})`
        );
      }

      const result = await response.json();
      const list = Array.isArray(result) ? result : [];

      if (selectedOption === "Pendientes") {
        const pendingItems = list
          .filter((item) => item.state === "Pendiente")
          .map((item) => ({
            ...item,
            serviciosList: parseServicios(item.servicios),
            customerName:
              item.customer?.name || `Cliente #${item.customer_id || "N/A"}`,
            vehiclePlate:
              item.vehicle?.plate || `Vehículo #${item.vehicle_id || "N/A"}`,
            workerName: item.worker?.name || "Sin asignar",
          }));
        setData(pendingItems);
      } else {
        setData(list);
      }
    } catch (err) {
      console.error(
        `Error al obtener los ${selectedOption.toLowerCase()}:`,
        err
      );
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }, [API_URL, selectedOption, setNumber, token]);

  useEffect(() => {
    fetchData();
  }, [fetchData]);

  const query = (searchTerm || "").toLowerCase().trim();

  const filteredData = data.filter((item) => {
    if (!query) return true;
    switch (selectedOption) {
      case "Productos":
        return (
          String(item.id).toLowerCase().includes(query) ||
          item.name?.toLowerCase().includes(query)
        );
      case "Clientes":
        return (
          String(item.id).toLowerCase().includes(query) ||
          item.name?.toLowerCase().includes(query) ||
          item.phone?.toLowerCase().includes(query) ||
          item.email?.toLowerCase().includes(query)
        );
      case "Vehiculos":
        return (
          item.plate?.toLowerCase().includes(query) ||
          item.make?.toLowerCase().includes(query) ||
          item.model?.toLowerCase().includes(query) ||
          item.customer?.name?.toLowerCase().includes(query)
        );
      case "Ventas": {
        const cName = item.customer?.name || "";
        const invNum = item.invoice?.invoice_number || `FACT-${item.id}`;
        const vPlate = item.vehicle?.plate || "";
        return (
          cName.toLowerCase().includes(query) ||
          invNum.toLowerCase().includes(query) ||
          vPlate.toLowerCase().includes(query)
        );
      }
      case "Pendientes":
        return (
          item.customerName?.toLowerCase().includes(query) ||
          item.vehiclePlate?.toLowerCase().includes(query) ||
          item.workerName?.toLowerCase().includes(query)
        );
      case "Movimientos": {
        const pName =
          item.product_name || item.inventory?.product?.name || "";
        const reason = item.reason || "";
        return (
          pName.toLowerCase().includes(query) ||
          reason.toLowerCase().includes(query) ||
          item.movement_type?.toLowerCase().includes(query)
        );
      }
      default:
        return true;
    }
  });

  const handleDelete = async (id) => {
    if (!window.confirm("¿Estás seguro de que deseas eliminar este registro?")) {
      return;
    }
    try {
      let endpoint = "";
      switch (selectedOption) {
        case "Productos":
          endpoint = "products";
          break;
        case "Clientes":
          endpoint = "customers";
          break;
        case "Vehiculos":
          endpoint = "vehicles";
          break;
        case "Ventas":
          endpoint = "sales";
          break;
        case "Pendientes":
          endpoint = "schedules";
          break;
        case "Movimientos":
          endpoint = "inventory-movements";
          break;
        default:
          return;
      }

      const response = await fetch(`${API_URL}/${endpoint}/${id}`, {
        method: "DELETE",
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: "application/json",
        },
      });

      if (response.ok) {
        setData((prev) => prev.filter((item) => item.id !== id));
      } else {
        throw new Error(
          `Error al eliminar ${selectedOption.toLowerCase()}: ${response.status}`
        );
      }
    } catch (err) {
      console.error("Error al eliminar el elemento:", err);
      setError(err.message);
    }
  };

  const openModal = (item) => {
    if (selectedOption === "Productos") {
      setProductUpdate({
        id: item.id,
        name: item.name || "",
        price: item.price ?? 0,
        stock: item.stock ?? 0,
      });
    } else if (selectedOption === "Clientes") {
      setCustomerUpdate({
        id: item.id,
        name: item.name || "",
        phone: item.phone || "",
        email: item.email || "",
      });
    } else if (selectedOption === "Vehiculos") {
      setVehicleUpdate({
        id: item.id,
        plate: item.plate || "",
        make: item.make || "",
        model: item.model || "",
        customer_id: item.customer_id || "",
        current_mileage:
          item.current_mileage != null ? String(item.current_mileage) : "",
        next_oil_change_km:
          item.next_oil_change_km != null ? String(item.next_oil_change_km) : "",
        soat_due_date: item.soat_due_date
          ? String(item.soat_due_date).slice(0, 10)
          : "",
        tecno_due_date: item.tecno_due_date
          ? String(item.tecno_due_date).slice(0, 10)
          : "",
      });
    }
    setIsModalOpen(true);
  };

  const closeModal = () => {
    setIsModalOpen(false);
  };

  const handleChangeProduct = (e) => {
    const { name, value } = e.target;
    setProductUpdate((prev) => ({
      ...prev,
      [name]: value,
    }));
  };

  const handleChangeCustomer = (e) => {
    const { name, value } = e.target;
    setCustomerUpdate((prev) => ({
      ...prev,
      [name]: value,
    }));
  };

  const handleChangeVehicle = (e) => {
    const { name, value } = e.target;
    setVehicleUpdate((prev) => ({
      ...prev,
      [name]: value,
    }));
  };

  const handleSaveProduct = async () => {
    try {
      const response = await fetch(`${API_URL}/products/${productUpdate.id}`, {
        method: "PUT",
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${token}`,
          Accept: "application/json",
        },
        body: JSON.stringify({
          name: productUpdate.name,
          price: Number(productUpdate.price),
          stock:
            productUpdate.stock !== "" ? Number(productUpdate.stock) : undefined,
        }),
      });

      if (response.ok) {
        closeModal();
        await fetchData();
      }
    } catch (err) {
      console.error("Error al actualizar producto:", err);
    }
  };

  const handleSaveCustomer = async () => {
    try {
      const response = await fetch(
        `${API_URL}/customers/${customerUpdate.id}`,
        {
          method: "PUT",
          headers: {
            "Content-Type": "application/json",
            Authorization: `Bearer ${token}`,
            Accept: "application/json",
          },
          body: JSON.stringify(customerUpdate),
        }
      );

      if (response.ok) {
        closeModal();
        await fetchData();
      }
    } catch (err) {
      console.error("Error al actualizar cliente:", err);
    }
  };

  const handleSaveVehicle = async () => {
    try {
      const payload = {
        plate: vehicleUpdate.plate,
        make: vehicleUpdate.make,
        model: vehicleUpdate.model,
        customer_id: vehicleUpdate.customer_id || undefined,
        current_mileage:
          vehicleUpdate.current_mileage !== ""
            ? Number(vehicleUpdate.current_mileage)
            : null,
        next_oil_change_km:
          vehicleUpdate.next_oil_change_km !== ""
            ? Number(vehicleUpdate.next_oil_change_km)
            : null,
        soat_due_date: vehicleUpdate.soat_due_date || null,
        tecno_due_date: vehicleUpdate.tecno_due_date || null,
      };
      const response = await fetch(`${API_URL}/vehicles/${vehicleUpdate.id}`, {
        method: "PUT",
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${token}`,
          Accept: "application/json",
        },
        body: JSON.stringify(payload),
      });

      if (response.ok) {
        closeModal();
        await fetchData();
      }
    } catch (err) {
      console.error("Error al actualizar vehículo:", err);
    }
  };

  const handleUpdateScheduleStatus = async (item, nextStatus) => {
    try {
      const response = await fetch(`${API_URL}/schedules/${item.id}`, {
        method: "PUT",
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${token}`,
          Accept: "application/json",
        },
        body: JSON.stringify({
          workshop_status: nextStatus,
        }),
      });
      if (response.ok) {
        setData((prev) =>
          prev.map((row) =>
            row.id === item.id ? { ...row, workshop_status: nextStatus } : row
          )
        );
      }
    } catch (err) {
      console.error("Error actualizando estado de orden:", err);
    }
  };

  const notifyOrderWhatsApp = (item) => {
    const rawPhone = String(item.customer?.phone || "").replace(/\D/g, "");
    if (!rawPhone) {
      window.alert("Este cliente no tiene un número de teléfono/WhatsApp registrado.");
      return;
    }
    const phone = rawPhone.length === 10 ? `57${rawPhone}` : rawPhone;
    const status = item.workshop_status || "Recibido";
    const servicesText =
      item.serviciosList && item.serviciosList.length > 0
        ? item.serviciosList.join(", ")
        : "Mantenimiento general";
    let msg = `Hola *${item.customerName}*, te escribimos de *FactuPOS Taller*.\n\n`;
    if (status === "Listo para Entrega") {
      msg += `✅ Tu vehículo placa *${item.vehiclePlate}* ya se encuentra *LISTO PARA ENTREGA*.\n`;
    } else {
      msg += `🔧 El estado actual de tu vehículo placa *${item.vehiclePlate}* en el taller es: *${status}*.\n`;
    }
    msg += `📋 *Servicios:* ${servicesText}\n\n¡Gracias por confiar en nosotros!`;

    window.open(
      `https://wa.me/${phone}?text=${encodeURIComponent(msg)}`,
      "_blank",
      "noopener,noreferrer"
    );
  };

  const notifyVehicleReminderWhatsApp = (item) => {
    const rawPhone = String(item.customer?.phone || "").replace(/\D/g, "");
    if (!rawPhone) {
      window.alert("El propietario no tiene un número de teléfono/WhatsApp registrado.");
      return;
    }
    const phone = rawPhone.length === 10 ? `57${rawPhone}` : rawPhone;
    const ownerName = item.customer?.name || "Cliente";
    let msg = `Hola *${ownerName}*, te saludamos de *FactuPOS Taller*.\n\nTe enviamos el recordatorio preventivo para tu vehículo *${item.make} ${item.model}* placa *${item.plate}*:\n`;
    if (item.current_mileage) {
      msg += `• Kilometraje registrado: ${Number(item.current_mileage).toLocaleString("es-CO")} km\n`;
    }
    if (item.next_oil_change_km) {
      msg += `• Próximo cambio de aceite sugerido: ${Number(item.next_oil_change_km).toLocaleString("es-CO")} km\n`;
    }
    if (item.soat_due_date) {
      msg += `• Vencimiento SOAT: ${String(item.soat_due_date).slice(0, 10)}\n`;
    }
    if (item.tecno_due_date) {
      msg += `• Vencimiento Tecnomecánica: ${String(item.tecno_due_date).slice(0, 10)}\n`;
    }
    msg += `\nAgenda tu cita con nosotros cuando lo necesites.`;

    window.open(
      `https://wa.me/${phone}?text=${encodeURIComponent(msg)}`,
      "_blank",
      "noopener,noreferrer"
    );
  };

  const formatPrice = (price) => {
    const num = Number(price);
    if (price == null || isNaN(num)) return "0";
    return num.toLocaleString("es-CO");
  };

  const formatDateTime = (dateTime) => {
    if (!dateTime) return "N/A";
    const date = new Date(dateTime);
    return isNaN(date)
      ? "Fecha inválida"
      : date.toLocaleDateString("es-CO", {
          year: "numeric",
          month: "2-digit",
          day: "2-digit",
        });
  };

  if (loading) {
    return (
      <div className="py-16 text-center text-slate-500 font-medium">
        Cargando {selectedOption.toLowerCase()}...
      </div>
    );
  }

  if (error) {
    return (
      <div className="p-6 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm flex items-center justify-between">
        <span>Error: {error}</span>
        <button
          type="button"
          onClick={fetchData}
          className="px-3 py-1.5 bg-red-600 text-white rounded-lg text-xs font-semibold"
        >
          Reintentar
        </button>
      </div>
    );
  }

  if (filteredData.length === 0) {
    return (
      <div className="py-16 text-center text-slate-400 font-medium">
        No se encontraron registros en {selectedOption.toLowerCase()}.
      </div>
    );
  }

  // 1. PRODUCTOS
  if (selectedOption === "Productos") {
    return (
      <div className="w-full">
        <table className="w-full text-sm text-left border-collapse">
          <thead>
            <tr className="border-b-2 border-slate-200 text-slate-600 uppercase text-xs bg-slate-50">
              <th className="py-3 px-4">Código</th>
              <th className="py-3 px-4">Nombre del Producto</th>
              <th className="py-3 px-4 text-right">Precio Unitario</th>
              <th className="py-3 px-4 text-center">Stock</th>
              <th className="py-3 px-4 text-center">Acciones</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {filteredData.map((item) => {
              const stock = Number(item.stock ?? 0);
              return (
                <tr key={item.id} className="hover:bg-slate-50 transition-colors">
                  <td className="py-3 px-4 font-mono text-xs font-bold text-slate-500">
                    #{item.id}
                  </td>
                  <td className="py-3 px-4 font-semibold text-slate-800">
                    {item.name}
                  </td>
                  <td className="py-3 px-4 text-right font-bold text-[#023047]">
                    $ {formatPrice(item.price)}
                  </td>
                  <td className="py-3 px-4 text-center">
                    <span
                      className={`inline-block px-2.5 py-0.5 rounded-full text-xs font-bold ${
                        stock <= 0
                          ? "bg-red-100 text-red-700"
                          : stock <= 5
                          ? "bg-amber-100 text-amber-800"
                          : "bg-emerald-100 text-emerald-700"
                      }`}
                    >
                      {stock} und.
                    </span>
                  </td>
                  <td className="py-3 px-4">
                    <div className="flex items-center justify-center gap-2">
                      <button
                        type="button"
                        onClick={() => openModal(item)}
                        className="blue flex items-center gap-1 text-xs py-1.5 px-3"
                      >
                        <FaEdit /> Editar
                      </button>
                      <button
                        type="button"
                        onClick={() => handleDelete(item.id)}
                        className="rojo flex items-center gap-1 text-xs py-1.5 px-3"
                      >
                        <FaTrash /> Eliminar
                      </button>
                    </div>
                  </td>
                </tr>
              );
            })}
          </tbody>
        </table>

        <Modalmini show={isModalOpen} onClose={closeModal}>
          <div className="flex flex-col w-full p-5 space-y-4 text-left">
            <div className="border-b border-slate-200 pb-3">
              <h2 className="text-lg font-extrabold text-[#023047]">
                Modificar Producto #{productUpdate.id}
              </h2>
              <p className="text-xs text-slate-500">
                Actualiza el nombre, precio de venta o existencias en inventario
              </p>
            </div>

            <div className="space-y-3">
              <div>
                <label className="block text-xs font-bold uppercase text-slate-600 mb-1">
                  Nombre del Producto
                </label>
                <input
                  type="text"
                  name="name"
                  value={productUpdate.name}
                  onChange={handleChangeProduct}
                  className="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#219ebc]"
                />
              </div>

              <div>
                <label className="block text-xs font-bold uppercase text-slate-600 mb-1">
                  Precio Unitario ($)
                </label>
                <input
                  type="number"
                  name="price"
                  min="0"
                  value={productUpdate.price}
                  onChange={handleChangeProduct}
                  className="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#219ebc]"
                />
              </div>

              <div>
                <label className="block text-xs font-bold uppercase text-slate-600 mb-1">
                  Stock Actual (Unidades)
                </label>
                <input
                  type="number"
                  name="stock"
                  min="0"
                  value={productUpdate.stock}
                  onChange={handleChangeProduct}
                  className="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#219ebc]"
                />
              </div>
            </div>

            <div className="flex justify-end gap-2 pt-3 border-t border-slate-200">
              <button type="button" className="rojo" onClick={closeModal}>
                Cancelar
              </button>
              <button
                type="button"
                className="blue"
                onClick={handleSaveProduct}
              >
                Guardar Cambios
              </button>
            </div>
          </div>
        </Modalmini>
      </div>
    );
  }

  // 2. CLIENTES
  if (selectedOption === "Clientes") {
    return (
      <div className="w-full">
        <table className="w-full text-sm text-left border-collapse">
          <thead>
            <tr className="border-b-2 border-slate-200 text-slate-600 uppercase text-xs bg-slate-50">
              <th className="py-3 px-4 text-center">Historial</th>
              <th className="py-3 px-4">ID / Código</th>
              <th className="py-3 px-4">Nombre Completo</th>
              <th className="py-3 px-4">Teléfono</th>
              <th className="py-3 px-4">Correo Electrónico</th>
              <th className="py-3 px-4 text-center">Acciones</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {filteredData.map((item) => (
              <tr key={item.id} className="hover:bg-slate-50 transition-colors">
                <td className="py-3 px-4 text-center">
                  <div className="flex justify-center">
                    <ReportCustomer
                      id={item.id}
                      name={item.name}
                      phone={item.phone}
                      email={item.email}
                    />
                  </div>
                </td>
                <td className="py-3 px-4 font-mono text-xs font-bold text-slate-500">
                  #{item.id}
                </td>
                <td className="py-3 px-4 font-semibold text-slate-800">
                  {item.name}
                </td>
                <td className="py-3 px-4 text-slate-600">
                  {item.phone || "No registrado"}
                </td>
                <td className="py-3 px-4 text-slate-600">
                  {item.email || "No registrado"}
                </td>
                <td className="py-3 px-4">
                  <div className="flex items-center justify-center gap-2">
                    <button
                      type="button"
                      onClick={() => openModal(item)}
                      className="blue flex items-center gap-1 text-xs py-1.5 px-3"
                    >
                      <FaEdit /> Editar
                    </button>
                    <button
                      type="button"
                      onClick={() => handleDelete(item.id)}
                      className="rojo flex items-center gap-1 text-xs py-1.5 px-3"
                    >
                      <FaTrash /> Eliminar
                    </button>
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>

        <Modalmini show={isModalOpen} onClose={closeModal}>
          <div className="flex flex-col w-full p-5 space-y-4 text-left">
            <div className="border-b border-slate-200 pb-3">
              <h2 className="text-lg font-extrabold text-[#023047]">
                Modificar Cliente #{customerUpdate.id}
              </h2>
              <p className="text-xs text-slate-500">
                Actualiza los datos de contacto del cliente
              </p>
            </div>

            <div className="space-y-3">
              <div>
                <label className="block text-xs font-bold uppercase text-slate-600 mb-1">
                  Nombre Completo
                </label>
                <input
                  type="text"
                  name="name"
                  value={customerUpdate.name}
                  onChange={handleChangeCustomer}
                  className="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#219ebc]"
                />
              </div>

              <div>
                <label className="block text-xs font-bold uppercase text-slate-600 mb-1">
                  Teléfono / Celular
                </label>
                <input
                  type="text"
                  name="phone"
                  value={customerUpdate.phone}
                  onChange={handleChangeCustomer}
                  className="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#219ebc]"
                />
              </div>

              <div>
                <label className="block text-xs font-bold uppercase text-slate-600 mb-1">
                  Correo Electrónico
                </label>
                <input
                  type="email"
                  name="email"
                  value={customerUpdate.email}
                  onChange={handleChangeCustomer}
                  className="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#219ebc]"
                />
              </div>
            </div>

            <div className="flex justify-end gap-2 pt-3 border-t border-slate-200">
              <button type="button" className="rojo" onClick={closeModal}>
                Cancelar
              </button>
              <button
                type="button"
                className="blue"
                onClick={handleSaveCustomer}
              >
                Guardar Cambios
              </button>
            </div>
          </div>
        </Modalmini>
      </div>
    );
  }

  // 3. VEHÍCULOS
  if (selectedOption === "Vehiculos") {
    const todayStr = new Date().toISOString().split("T")[0];
    return (
      <div className="w-full">
        <table className="w-full text-sm text-left border-collapse">
          <thead>
            <tr className="border-b-2 border-slate-200 text-slate-600 uppercase text-xs bg-slate-50">
              <th className="py-3 px-4 text-center">Historial</th>
              <th className="py-3 px-4">Placa</th>
              <th className="py-3 px-4">Marca / Línea</th>
              <th className="py-3 px-4">Propietario</th>
              <th className="py-3 px-4">Km / Próx. Aceite</th>
              <th className="py-3 px-4">SOAT / Tecnomecánica</th>
              <th className="py-3 px-4 text-center">Acciones</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {filteredData.map((item) => {
              const oilOverdue =
                item.current_mileage != null &&
                item.next_oil_change_km != null &&
                Number(item.current_mileage) >= Number(item.next_oil_change_km);
              const soatExpired =
                item.soat_due_date && item.soat_due_date < todayStr;
              const tecnoExpired =
                item.tecno_due_date && item.tecno_due_date < todayStr;

              return (
                <tr key={item.id} className="hover:bg-slate-50 transition-colors">
                  <td className="py-3 px-4 text-center">
                    <div className="flex justify-center">
                      <ReportVehicle
                        id={item.id}
                        plate={item.plate}
                        make={item.make}
                        model={item.model}
                        idCustomer={item.customer_id}
                        ownerName={item.customer?.name}
                      />
                    </div>
                  </td>
                  <td className="py-3 px-4">
                    <span className="inline-block bg-amber-100 text-amber-900 px-2.5 py-0.5 rounded font-mono text-xs font-extrabold border border-amber-300">
                      {item.plate}
                    </span>
                  </td>
                  <td className="py-3 px-4">
                    <p className="font-semibold text-slate-800">{item.make}</p>
                    <p className="text-xs text-slate-500">{item.model}</p>
                  </td>
                  <td className="py-3 px-4 text-slate-600">
                    <p className="font-medium text-slate-700">
                      {item.customer?.name || `Cliente #${item.customer_id}`}
                    </p>
                    {item.customer?.phoneNumber && (
                      <p className="text-xs text-slate-400">
                        Tel: {item.customer.phoneNumber}
                      </p>
                    )}
                  </td>
                  <td className="py-3 px-4">
                    {item.current_mileage != null ||
                    item.next_oil_change_km != null ? (
                      <div className="space-y-1 text-xs">
                        <p className="text-slate-700">
                          Actual:{" "}
                          <span className="font-bold">
                            {item.current_mileage != null
                              ? `${formatPrice(item.current_mileage)} km`
                              : "—"}
                          </span>
                        </p>
                        {item.next_oil_change_km != null && (
                          <span
                            className={`inline-block px-2 py-0.5 rounded-full font-bold ${
                              oilOverdue
                                ? "bg-rose-100 text-rose-700 border border-rose-300"
                                : "bg-emerald-50 text-emerald-700 border border-emerald-200"
                            }`}
                          >
                            {oilOverdue ? "⚠️ " : "🛢️ "}
                            Próx: {formatPrice(item.next_oil_change_km)} km
                          </span>
                        )}
                      </div>
                    ) : (
                      <span className="text-xs text-slate-400">Sin registro</span>
                    )}
                  </td>
                  <td className="py-3 px-4">
                    <div className="space-y-1 text-xs">
                      {item.soat_due_date ? (
                        <div>
                          <span
                            className={`inline-block px-2 py-0.5 rounded font-semibold ${
                              soatExpired
                                ? "bg-rose-100 text-rose-700 border border-rose-300"
                                : "bg-slate-100 text-slate-700"
                            }`}
                          >
                            SOAT: {item.soat_due_date}
                            {soatExpired ? " (Vencido)" : ""}
                          </span>
                        </div>
                      ) : null}
                      {item.tecno_due_date ? (
                        <div>
                          <span
                            className={`inline-block px-2 py-0.5 rounded font-semibold ${
                              tecnoExpired
                                ? "bg-rose-100 text-rose-700 border border-rose-300"
                                : "bg-slate-100 text-slate-700"
                            }`}
                          >
                            Tecno: {item.tecno_due_date}
                            {tecnoExpired ? " (Vencida)" : ""}
                          </span>
                        </div>
                      ) : null}
                      {!item.soat_due_date && !item.tecno_due_date && (
                        <span className="text-slate-400">Sin fechas</span>
                      )}
                    </div>
                  </td>
                  <td className="py-3 px-4">
                    <div className="flex items-center justify-center gap-1.5 flex-wrap">
                      <button
                        type="button"
                        onClick={() => notifyVehicleReminderWhatsApp(item)}
                        title="Enviar recordatorio de mantenimiento / documentos por WhatsApp"
                        className="bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg flex items-center gap-1 text-xs py-1.5 px-2.5 font-bold transition-colors cursor-pointer"
                      >
                        <FaWhatsapp /> Recordar
                      </button>
                      <button
                        type="button"
                        onClick={() => openModal(item)}
                        className="blue flex items-center gap-1 text-xs py-1.5 px-2.5"
                      >
                        <FaEdit /> Editar
                      </button>
                      <button
                        type="button"
                        onClick={() => handleDelete(item.id)}
                        className="rojo flex items-center gap-1 text-xs py-1.5 px-2.5"
                      >
                        <FaTrash />
                      </button>
                    </div>
                  </td>
                </tr>
              );
            })}
          </tbody>
        </table>

        <Modalmini show={isModalOpen} onClose={closeModal}>
          <div className="flex flex-col w-full p-5 space-y-4 text-left">
            <div className="border-b border-slate-200 pb-3">
              <h2 className="text-lg font-extrabold text-[#023047]">
                Modificar Vehículo ({vehicleUpdate.plate})
              </h2>
              <p className="text-xs text-slate-500">
                Actualiza datos básicos, kilometraje y vencimientos de ley
              </p>
            </div>

            <div className="space-y-3">
              <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                  <label className="block text-xs font-bold uppercase text-slate-600 mb-1">
                    Placa
                  </label>
                  <input
                    type="text"
                    name="plate"
                    value={vehicleUpdate.plate}
                    onChange={handleChangeVehicle}
                    className="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm uppercase focus:outline-none focus:ring-2 focus:ring-[#219ebc]"
                  />
                </div>

                <div>
                  <label className="block text-xs font-bold uppercase text-slate-600 mb-1">
                    Marca
                  </label>
                  <input
                    type="text"
                    name="make"
                    value={vehicleUpdate.make}
                    onChange={handleChangeVehicle}
                    className="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#219ebc]"
                  />
                </div>

                <div>
                  <label className="block text-xs font-bold uppercase text-slate-600 mb-1">
                    Modelo / Línea
                  </label>
                  <input
                    type="text"
                    name="model"
                    value={vehicleUpdate.model}
                    onChange={handleChangeVehicle}
                    className="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#219ebc]"
                  />
                </div>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2 border-t border-slate-100">
                <div>
                  <label className="block text-xs font-bold uppercase text-slate-600 mb-1">
                    Kilometraje Actual (km)
                  </label>
                  <input
                    type="number"
                    min="0"
                    name="current_mileage"
                    value={vehicleUpdate.current_mileage}
                    onChange={handleChangeVehicle}
                    placeholder="Ej: 45000"
                    className="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#219ebc]"
                  />
                </div>

                <div>
                  <label className="block text-xs font-bold uppercase text-slate-600 mb-1">
                    Próximo Cambio Aceite (km)
                  </label>
                  <input
                    type="number"
                    min="0"
                    name="next_oil_change_km"
                    value={vehicleUpdate.next_oil_change_km}
                    onChange={handleChangeVehicle}
                    placeholder="Ej: 48000"
                    className="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#219ebc]"
                  />
                </div>

                <div>
                  <label className="block text-xs font-bold uppercase text-slate-600 mb-1">
                    Vencimiento SOAT
                  </label>
                  <input
                    type="date"
                    name="soat_due_date"
                    value={vehicleUpdate.soat_due_date}
                    onChange={handleChangeVehicle}
                    className="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#219ebc]"
                  />
                </div>

                <div>
                  <label className="block text-xs font-bold uppercase text-slate-600 mb-1">
                    Vencimiento Tecnomecánica
                  </label>
                  <input
                    type="date"
                    name="tecno_due_date"
                    value={vehicleUpdate.tecno_due_date}
                    onChange={handleChangeVehicle}
                    className="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#219ebc]"
                  />
                </div>
              </div>
            </div>

            <div className="flex justify-end gap-2 pt-3 border-t border-slate-200">
              <button type="button" className="rojo" onClick={closeModal}>
                Cancelar
              </button>
              <button
                type="button"
                className="blue"
                onClick={handleSaveVehicle}
              >
                Guardar Cambios
              </button>
            </div>
          </div>
        </Modalmini>
      </div>
    );
  }

  // 4. VENTAS
  if (selectedOption === "Ventas") {
    return (
      <div className="w-full">
        <table className="w-full text-sm text-left border-collapse">
          <thead>
            <tr className="border-b-2 border-slate-200 text-slate-600 uppercase text-xs bg-slate-50">
              <th className="py-3 px-4">Factura</th>
              <th className="py-3 px-4">Cliente</th>
              <th className="py-3 px-4">Vehículo</th>
              <th className="py-3 px-4">Mecánico</th>
              <th className="py-3 px-4">Fecha</th>
              <th className="py-3 px-4 text-right">Total</th>
              <th className="py-3 px-4 text-center">Acciones</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {filteredData.map((item) => (
              <tr key={item.id} className="hover:bg-slate-50 transition-colors">
                <td className="py-3 px-4">
                  <FactuView
                    id={item.id}
                    customerID={item.customer_id}
                    vehicleID={item.vehicle_id}
                    total={item.total}
                    invoiceNumber={item.invoice?.invoice_number}
                    saleData={item}
                  />
                </td>
                <td className="py-3 px-4 font-semibold text-slate-800">
                  {item.customer?.name || `Cliente #${item.customer_id}`}
                </td>
                <td className="py-3 px-4 text-slate-600">
                  {item.vehicle?.plate ? (
                    <span className="inline-block bg-amber-100 text-amber-900 px-2 py-0.5 rounded font-mono text-xs font-bold border border-amber-300">
                      {item.vehicle.plate}
                    </span>
                  ) : (
                    <span className="text-xs text-slate-400">Mostrador</span>
                  )}
                </td>
                <td className="py-3 px-4 text-slate-600">
                  {item.worker?.name || "General"}
                </td>
                <td className="py-3 px-4 text-slate-500">
                  {formatDateTime(item.created_at)}
                </td>
                <td className="py-3 px-4 text-right font-extrabold text-[#023047]">
                  $ {formatPrice(item.total)}
                </td>
                <td className="py-3 px-4 text-center">
                  <button
                    type="button"
                    className="rojo flex items-center gap-1 text-xs py-1.5 px-3 mx-auto"
                    onClick={() => handleDelete(item.id)}
                  >
                    <FaTrash /> Eliminar
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    );
  }

  // 5. PENDIENTES (CITAS AGENDADAS)
  if (selectedOption === "Pendientes") {
    return (
      <div className="w-full">
        <table className="w-full text-sm text-left border-collapse">
          <thead>
            <tr className="border-b-2 border-slate-200 text-slate-600 uppercase text-xs bg-slate-50">
              <th className="py-3 px-4">Fecha</th>
              <th className="py-3 px-4">Cliente</th>
              <th className="py-3 px-4">Vehículo</th>
              <th className="py-3 px-4">Mecánico</th>
              <th className="py-3 px-4">Estado Taller</th>
              <th className="py-3 px-4">Servicios / Recepción</th>
              <th className="py-3 px-4 text-center">Acciones</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {filteredData.map((item) => (
              <tr key={item.id} className="hover:bg-slate-50 transition-colors">
                <td className="py-3 px-4 text-slate-500 whitespace-nowrap">
                  {formatDateTime(item.created_at)}
                </td>
                <td className="py-3 px-4 font-semibold text-slate-800">
                  <p>{item.customerName}</p>
                  {item.customerPhone && (
                    <p className="text-xs text-slate-400 font-normal">
                      Tel: {item.customerPhone}
                    </p>
                  )}
                </td>
                <td className="py-3 px-4">
                  <span className="inline-block bg-amber-100 text-amber-900 px-2.5 py-0.5 rounded font-mono text-xs font-bold border border-amber-300">
                    {item.vehiclePlate}
                  </span>
                  {item.mileage != null && (
                    <p className="text-[11px] text-slate-500 mt-1">
                      {formatPrice(item.mileage)} km
                    </p>
                  )}
                </td>
                <td className="py-3 px-4 text-slate-600">{item.workerName}</td>
                <td className="py-3 px-4">
                  <select
                    value={item.workshop_status || "Recibido"}
                    onChange={(e) =>
                      handleUpdateScheduleStatus(item.id, e.target.value)
                    }
                    className="px-2.5 py-1.5 rounded-lg border border-slate-300 bg-white text-xs font-bold text-[#023047] focus:outline-none focus:ring-2 focus:ring-[#219ebc]"
                  >
                    {WORKSHOP_STATUS_OPTIONS.map((statusOpt) => (
                      <option key={statusOpt} value={statusOpt}>
                        {statusOpt}
                      </option>
                    ))}
                  </select>
                </td>
                <td className="py-3 px-4">
                  {item.serviciosList && item.serviciosList.length > 0 ? (
                    <div className="flex flex-wrap gap-1">
                      {item.serviciosList.map((servicio, index) => (
                        <span
                          key={index}
                          className="bg-sky-50 text-[#023047] border border-sky-200 px-2 py-0.5 rounded text-xs font-medium"
                        >
                          {servicio}
                        </span>
                      ))}
                    </div>
                  ) : (
                    <span className="text-slate-400 text-xs">Sin especificar</span>
                  )}
                  {item.notes && (
                    <p className="text-xs text-slate-500 italic mt-1">
                      Nota: {item.notes}
                    </p>
                  )}
                </td>
                <td className="py-3 px-4 text-center">
                  <div className="flex items-center justify-center gap-1.5">
                    <button
                      type="button"
                      onClick={() => notifyOrderWhatsApp(item)}
                      title="Notificar estado de la orden por WhatsApp"
                      className="bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg flex items-center gap-1 text-xs py-1.5 px-2.5 font-bold transition-colors cursor-pointer"
                    >
                      <FaWhatsapp /> Avisar
                    </button>
                    <button
                      type="button"
                      className="rojo flex items-center gap-1 text-xs py-1.5 px-2.5"
                      onClick={() => handleDelete(item.id)}
                    >
                      <FaTrash />
                    </button>
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    );
  }

  // 6. MOVIMIENTOS DE INVENTARIO
  if (selectedOption === "Movimientos") {
    return (
      <div className="w-full">
        <table className="w-full text-sm text-left border-collapse">
          <thead>
            <tr className="border-b-2 border-slate-200 text-slate-600 uppercase text-xs bg-slate-50">
              <th className="py-3 px-4">ID</th>
              <th className="py-3 px-4">Producto</th>
              <th className="py-3 px-4 text-center">Tipo</th>
              <th className="py-3 px-4 text-center">Cantidad</th>
              <th className="py-3 px-4">Motivo / Referencia</th>
              <th className="py-3 px-4 text-right">Fecha</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {filteredData.map((item) => {
              const isIncoming = item.movement_type === "incoming";
              const prodName =
                item.product_name ||
                item.inventory?.product?.name ||
                `Inventario #${item.inventory_id}`;
              return (
                <tr key={item.id} className="hover:bg-slate-50 transition-colors">
                  <td className="py-3 px-4 font-mono text-xs text-slate-500">
                    #{item.id}
                  </td>
                  <td className="py-3 px-4 font-semibold text-slate-800">
                    {prodName}
                  </td>
                  <td className="py-3 px-4 text-center">
                    <span
                      className={`inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold ${
                        isIncoming
                          ? "bg-emerald-100 text-emerald-700"
                          : "bg-rose-100 text-rose-700"
                      }`}
                    >
                      {isIncoming ? <FaArrowUp /> : <FaArrowDown />}
                      {isIncoming ? "Entrada" : "Salida"}
                    </span>
                  </td>
                  <td className="py-3 px-4 text-center font-bold text-slate-800">
                    {isIncoming ? `+${item.quantity}` : `-${item.quantity}`}
                  </td>
                  <td className="py-3 px-4 text-slate-600">
                    {item.reason ||
                      (isIncoming ? "Ingreso de stock" : "Salida por venta")}
                  </td>
                  <td className="py-3 px-4 text-right text-slate-500">
                    {formatDateTime(item.movement_date || item.created_at)}
                  </td>
                </tr>
              );
            })}
          </tbody>
        </table>
      </div>
    );
  }

  return <div>No hay datos disponibles.</div>;
};

export default LookDetail;
