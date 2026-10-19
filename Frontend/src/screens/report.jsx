import React, { useState, useEffect, useCallback } from "react";
import DateTimeDisplay from "../components/DateTimeDisplay";
import FactuView from "../components/FactuView";
import {
  FaMoneyBillWave,
  FaTools,
  FaBoxOpen,
  FaUserCog,
  FaSyncAlt,
  FaPlus,
  FaTrash,
  FaCheckCircle,
} from "react-icons/fa";

const Report = () => {
  const API_URL = import.meta.env.VITE_API_URL;
  const token = localStorage.getItem("authToken");

  const [sales, setSales] = useState([]);
  const [workers, setWorkers] = useState([]);
  const [products, setProducts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [period, setPeriod] = useState("all"); // 'today' | 'month' | 'all'
  const [newWorkerName, setNewWorkerName] = useState("");
  const [statusMsg, setStatusMsg] = useState("");

  const fetchReportData = useCallback(async () => {
    setLoading(true);
    try {
      const headers = {
        Authorization: `Bearer ${token}`,
        Accept: "application/json",
      };

      const [salesRes, workersRes, productsRes] = await Promise.all([
        fetch(`${API_URL}/sales`, { headers }),
        fetch(`${API_URL}/workers`, { headers }),
        fetch(`${API_URL}/products`, { headers }),
      ]);

      if (salesRes.ok) {
        const sData = await salesRes.json();
        setSales(Array.isArray(sData) ? sData : []);
      }
      if (workersRes.ok) {
        const wData = await workersRes.json();
        setWorkers(Array.isArray(wData) ? wData : []);
      }
      if (productsRes.ok) {
        const pData = await productsRes.json();
        setProducts(Array.isArray(pData) ? pData : []);
      }
    } catch (error) {
      console.error("Error al cargar datos de reportes:", error);
    } finally {
      setLoading(false);
    }
  }, [API_URL, token]);

  useEffect(() => {
    fetchReportData();
  }, [fetchReportData]);

  const now = new Date();
  const filteredSales = sales.filter((sale) => {
    if (period === "all") return true;
    if (!sale.created_at) return false;
    const d = new Date(sale.created_at);
    if (isNaN(d)) return false;

    if (period === "today") {
      return (
        d.getFullYear() === now.getFullYear() &&
        d.getMonth() === now.getMonth() &&
        d.getDate() === now.getDate()
      );
    }
    if (period === "month") {
      return (
        d.getFullYear() === now.getFullYear() &&
        d.getMonth() === now.getMonth()
      );
    }
    return true;
  });

  const totalFacturado = filteredSales.reduce(
    (acc, s) => acc + Number(s.total || 0),
    0
  );

  const totalProductos = filteredSales.reduce((acc, s) => {
    const details = s.sale_details || s.saleDetails || [];
    const sub = details.reduce(
      (sum, d) => sum + Number(d.price_total || 0),
      0
    );
    return acc + sub;
  }, 0);

  const totalServicios = filteredSales.reduce((acc, s) => {
    const svcs = s.services || [];
    const sub = svcs.reduce((sum, srv) => sum + Number(srv.price || 0), 0);
    return acc + sub;
  }, 0);

  const lowStockProducts = products.filter(
    (p) => Number(p.stock ?? 0) <= 5
  );

  const formatPrice = (val) => {
    const num = Number(val || 0);
    return isNaN(num) ? "0" : num.toLocaleString("es-CO");
  };

  const formatDateTime = (dateTime) => {
    if (!dateTime) return "N/A";
    const d = new Date(dateTime);
    return isNaN(d) ? "N/A" : d.toLocaleDateString("es-CO");
  };

  const handleResetWorkerDay = async (worker) => {
    try {
      const response = await fetch(`${API_URL}/workers/${worker.id}`, {
        method: "PUT",
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${token}`,
          Accept: "application/json",
        },
        body: JSON.stringify({
          name: worker.name,
          totalDia: 0,
          reset: true,
        }),
      });
      if (response.ok) {
        setStatusMsg(`Se reinició el acumulado diario de ${worker.name}`);
        setTimeout(() => setStatusMsg(""), 3500);
        await fetchReportData();
      }
    } catch (error) {
      console.error("Error al reiniciar acumulado del mecánico:", error);
    }
  };

  const handleAddWorker = async (e) => {
    e.preventDefault();
    if (!newWorkerName.trim()) return;
    try {
      const response = await fetch(`${API_URL}/workers`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${token}`,
          Accept: "application/json",
        },
        body: JSON.stringify({
          name: newWorkerName.trim(),
          totalDia: 0,
        }),
      });
      if (response.ok) {
        setNewWorkerName("");
        setStatusMsg("Mecánico registrado exitosamente");
        setTimeout(() => setStatusMsg(""), 3500);
        await fetchReportData();
      }
    } catch (error) {
      console.error("Error al crear mecánico:", error);
    }
  };

  const handleDeleteWorker = async (id) => {
    if (!window.confirm("¿Deseas eliminar este mecánico?")) return;
    try {
      const response = await fetch(`${API_URL}/workers/${id}`, {
        method: "DELETE",
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: "application/json",
        },
      });
      if (response.ok) {
        await fetchReportData();
      }
    } catch (error) {
      console.error("Error al eliminar mecánico:", error);
    }
  };

  return (
    <div className="min-h-screen w-full flex flex-col bg-slate-100 p-4 md:p-6 gap-5">
      {/* Encabezado */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white px-5 py-3.5 rounded-xl shadow-sm border border-slate-200">
        <div>
          <h1 className="text-xl font-extrabold text-[#023047] tracking-tight">
            REPORTES, ESTADÍSTICAS Y CIERRE DE CAJA
          </h1>
          <p className="text-xs text-slate-500">
            Resumen financiero de ventas, repuestos, mano de obra y liquidación diaria de mecánicos
          </p>
        </div>
        <div className="flex items-center gap-3">
          <div className="flex bg-slate-100 p-1 rounded-lg border border-slate-200">
            {[
              { id: "today", label: "Hoy" },
              { id: "month", label: "Este Mes" },
              { id: "all", label: "Histórico" },
            ].map((tab) => (
              <button
                key={tab.id}
                type="button"
                onClick={() => setPeriod(tab.id)}
                className={`px-3 py-1 rounded-md text-xs font-bold transition-colors cursor-pointer ${
                  period === tab.id
                    ? "bg-[#023047] text-white shadow-sm"
                    : "text-slate-600 hover:text-slate-900"
                }`}
              >
                {tab.label}
              </button>
            ))}
          </div>
          <DateTimeDisplay />
        </div>
      </div>

      {statusMsg && (
        <div className="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-2.5 rounded-xl text-sm font-medium flex items-center gap-2">
          <FaCheckCircle className="text-emerald-600" />
          <span>{statusMsg}</span>
        </div>
      )}

      {/* Tarjetas de KPIs */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div className="bg-white p-5 rounded-xl shadow-sm border border-slate-200 flex items-center gap-4">
          <div className="w-12 h-12 rounded-xl bg-[#023047] text-amber-400 flex items-center justify-center text-2xl shrink-0">
            <FaMoneyBillWave />
          </div>
          <div>
            <span className="text-xs font-bold uppercase text-slate-400 block">
              Total Facturado ({filteredSales.length} ventas)
            </span>
            <p className="text-2xl font-extrabold text-[#023047]">
              $ {formatPrice(totalFacturado)}
            </p>
          </div>
        </div>

        <div className="bg-white p-5 rounded-xl shadow-sm border border-slate-200 flex items-center gap-4">
          <div className="w-12 h-12 rounded-xl bg-sky-100 text-[#023047] flex items-center justify-center text-2xl shrink-0">
            <FaBoxOpen />
          </div>
          <div>
            <span className="text-xs font-bold uppercase text-slate-400 block">
              Ingresos por Repuestos
            </span>
            <p className="text-2xl font-extrabold text-slate-800">
              $ {formatPrice(totalProductos)}
            </p>
          </div>
        </div>

        <div className="bg-white p-5 rounded-xl shadow-sm border border-slate-200 flex items-center gap-4">
          <div className="w-12 h-12 rounded-xl bg-amber-100 text-amber-800 flex items-center justify-center text-2xl shrink-0">
            <FaTools />
          </div>
          <div>
            <span className="text-xs font-bold uppercase text-slate-400 block">
              Ingresos Mano de Obra
            </span>
            <p className="text-2xl font-extrabold text-slate-800">
              $ {formatPrice(totalServicios)}
            </p>
          </div>
        </div>

        <div className="bg-white p-5 rounded-xl shadow-sm border border-slate-200 flex items-center gap-4">
          <div className="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-2xl shrink-0">
            <FaUserCog />
          </div>
          <div>
            <span className="text-xs font-bold uppercase text-slate-400 block">
              Mecánicos Activos
            </span>
            <p className="text-2xl font-extrabold text-slate-800">
              {workers.length}
            </p>
          </div>
        </div>
      </div>

      {/* Grilla Principal: Mecánicos y Stock Bajo */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-5">
        {/* Producción por Mecánico */}
        <div className="lg:col-span-2 bg-white rounded-xl shadow-sm border border-slate-200 p-5 flex flex-col gap-4">
          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-200 pb-3">
            <div>
              <h2 className="text-base font-extrabold text-[#023047]">
                Liquidación y Producción Diaria por Mecánico
              </h2>
              <p className="text-xs text-slate-500">
                Acumulado del día en servicios y ventas asignadas a cada técnico
              </p>
            </div>

            <form onSubmit={handleAddWorker} className="flex gap-2">
              <input
                type="text"
                placeholder="Nuevo mecánico..."
                value={newWorkerName}
                onChange={(e) => setNewWorkerName(e.target.value)}
                className="px-3 py-1.5 text-xs border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#219ebc]"
              />
              <button
                type="submit"
                className="blue flex items-center gap-1 text-xs py-1.5 px-3"
              >
                <FaPlus /> Agregar
              </button>
            </form>
          </div>

          {workers.length === 0 ? (
            <div className="py-8 text-center text-slate-400 text-sm">
              No hay mecánicos registrados. Agrega uno arriba.
            </div>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-sm text-left border-collapse">
                <thead>
                  <tr className="border-b border-slate-200 text-slate-500 uppercase text-xs bg-slate-50">
                    <th className="py-2.5 px-3">ID</th>
                    <th className="py-2.5 px-3">Nombre del Mecánico</th>
                    <th className="py-2.5 px-3 text-right">Acumulado del Día</th>
                    <th className="py-2.5 px-3 text-center">Acciones de Cierre</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {workers.map((w) => (
                    <tr key={w.id} className="hover:bg-slate-50">
                      <td className="py-2.5 px-3 font-mono text-xs text-slate-500">
                        #{w.id}
                      </td>
                      <td className="py-2.5 px-3 font-semibold text-slate-800">
                        {w.name}
                      </td>
                      <td className="py-2.5 px-3 text-right font-extrabold text-emerald-700">
                        $ {formatPrice(w.totalDia)}
                      </td>
                      <td className="py-2.5 px-3">
                        <div className="flex items-center justify-center gap-2">
                          <button
                            type="button"
                            onClick={() => handleResetWorkerDay(w)}
                            className="px-2.5 py-1 rounded-lg bg-amber-100 text-amber-900 hover:bg-amber-200 text-xs font-bold flex items-center gap-1 cursor-pointer transition-colors"
                            title="Reiniciar acumulado del día a $0"
                          >
                            <FaSyncAlt /> Cerrar Día
                          </button>
                          <button
                            type="button"
                            onClick={() => handleDeleteWorker(w.id)}
                            className="p-1.5 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white text-xs cursor-pointer transition-colors"
                            title="Eliminar mecánico"
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
          )}
        </div>

        {/* Alertas de Stock Bajo */}
        <div className="bg-white rounded-xl shadow-sm border border-slate-200 p-5 flex flex-col gap-3">
          <div className="border-b border-slate-200 pb-3">
            <h2 className="text-base font-extrabold text-[#023047]">
              Alertas de Inventario ({lowStockProducts.length})
            </h2>
            <p className="text-xs text-slate-500">
              Productos con 5 o menos unidades disponibles
            </p>
          </div>

          <div className="flex-1 overflow-y-auto max-h-64 divide-y divide-slate-100">
            {lowStockProducts.length === 0 ? (
              <div className="py-8 text-center text-emerald-600 text-sm font-medium">
                Todo el inventario tiene niveles óptimos de stock.
              </div>
            ) : (
              lowStockProducts.map((p) => (
                <div
                  key={p.id}
                  className="py-2.5 px-2 flex items-center justify-between text-sm"
                >
                  <div>
                    <p className="font-semibold text-slate-800">{p.name}</p>
                    <span className="text-xs text-slate-400">
                      Código #{p.id} • $ {formatPrice(p.price)}
                    </span>
                  </div>
                  <span
                    className={`px-2.5 py-0.5 rounded-full text-xs font-bold ${
                      Number(p.stock) <= 0
                        ? "bg-red-100 text-red-700"
                        : "bg-amber-100 text-amber-800"
                    }`}
                  >
                    {p.stock ?? 0} und.
                  </span>
                </div>
              ))
            )}
          </div>
        </div>
      </div>

      {/* Últimas Facturas del Período */}
      <div className="bg-white rounded-xl shadow-sm border border-slate-200 p-5 flex flex-col gap-3">
        <div className="flex items-center justify-between border-b border-slate-200 pb-3">
          <div>
            <h2 className="text-base font-extrabold text-[#023047]">
              Detalle de Facturas del Período ({filteredSales.length})
            </h2>
            <p className="text-xs text-slate-500">
              Haz clic en cualquier número de factura para previsualizar o reimprimir el soporte PDF
            </p>
          </div>
        </div>

        {loading ? (
          <div className="py-10 text-center text-slate-400 text-sm">
            Cargando movimientos...
          </div>
        ) : filteredSales.length === 0 ? (
          <div className="py-10 text-center text-slate-400 text-sm">
            No se registraron ventas en el período seleccionado.
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm text-left border-collapse">
              <thead>
                <tr className="border-b border-slate-200 text-slate-500 uppercase text-xs bg-slate-50">
                  <th className="py-3 px-4">Factura</th>
                  <th className="py-3 px-4">Cliente</th>
                  <th className="py-3 px-4">Vehículo</th>
                  <th className="py-3 px-4">Mecánico</th>
                  <th className="py-3 px-4">Pago</th>
                  <th className="py-3 px-4">Fecha</th>
                  <th className="py-3 px-4 text-right">Total</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {filteredSales.map((sale) => (
                  <tr key={sale.id} className="hover:bg-slate-50">
                    <td className="py-3 px-4">
                      <FactuView
                        id={sale.id}
                        customerID={sale.customer_id}
                        vehicleID={sale.vehicle_id}
                        total={sale.total}
                        invoiceNumber={sale.invoice?.invoice_number}
                        saleData={sale}
                      />
                    </td>
                    <td className="py-3 px-4 font-semibold text-slate-800">
                      {sale.customer?.name || `Cliente #${sale.customer_id}`}
                    </td>
                    <td className="py-3 px-4">
                      {sale.vehicle?.plate ? (
                        <span className="inline-block bg-amber-100 text-amber-900 px-2 py-0.5 rounded font-mono text-xs font-bold border border-amber-300">
                          {sale.vehicle.plate}
                        </span>
                      ) : (
                        <span className="text-xs text-slate-400">Mostrador</span>
                      )}
                    </td>
                    <td className="py-3 px-4 text-slate-600">
                      {sale.worker?.name || "General"}
                    </td>
                    <td className="py-3 px-4">
                      <span className="px-2 py-0.5 rounded bg-slate-100 text-slate-700 text-xs font-semibold">
                        {sale.payment_method || "Efectivo"}
                      </span>
                    </td>
                    <td className="py-3 px-4 text-slate-500">
                      {formatDateTime(sale.created_at)}
                    </td>
                    <td className="py-3 px-4 text-right font-extrabold text-[#023047]">
                      $ {formatPrice(sale.total)}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </div>
  );
};

export default Report;