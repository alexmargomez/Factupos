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
  FaCashRegister,
  FaReceipt,
  FaHandHoldingUsd,
} from "react-icons/fa";

const Report = () => {
  const API_URL = import.meta.env.VITE_API_URL;
  const token = localStorage.getItem("authToken");

  const [sales, setSales] = useState([]);
  const [workers, setWorkers] = useState([]);
  const [products, setProducts] = useState([]);
  const [expenses, setExpenses] = useState([]);
  const [loading, setLoading] = useState(true);
  const [period, setPeriod] = useState("all"); // 'today' | 'month' | 'all'
  const [newWorkerName, setNewWorkerName] = useState("");
  const [statusMsg, setStatusMsg] = useState("");

  const [newExpense, setNewExpense] = useState({
    description: "",
    category: "Insumos Taller",
    amount: "",
    payment_method: "Efectivo",
  });

  const fetchReportData = useCallback(async () => {
    setLoading(true);
    try {
      const headers = {
        Authorization: `Bearer ${token}`,
        Accept: "application/json",
      };

      const [salesRes, workersRes, productsRes, expensesRes] = await Promise.all([
        fetch(`${API_URL}/sales`, { headers }),
        fetch(`${API_URL}/workers`, { headers }),
        fetch(`${API_URL}/products`, { headers }),
        fetch(`${API_URL}/expenses`, { headers }).catch(() => ({ ok: false })),
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
      if (expensesRes && expensesRes.ok) {
        const eData = await expensesRes.json();
        setExpenses(Array.isArray(eData) ? eData : []);
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
  const matchesPeriod = (dateStr) => {
    if (period === "all") return true;
    if (!dateStr) return false;
    const d = new Date(dateStr);
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
  };

  const filteredSales = sales.filter((sale) => matchesPeriod(sale.created_at));
  const filteredExpenses = expenses.filter((exp) =>
    matchesPeriod(exp.created_at || exp.expense_date)
  );

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

  const costoProductosVendidos = filteredSales.reduce((acc, s) => {
    const details = s.sale_details || s.saleDetails || [];
    const subCost = details.reduce((sum, d) => {
      const qty = Number(d.quantity || 1);
      const unitCost =
        Number(d.product?.cost_price ?? 0) ||
        Number(products.find((p) => Number(p.id) === Number(d.product_id))?.cost_price ?? 0);
      return sum + qty * unitCost;
    }, 0);
    return acc + subCost;
  }, 0);

  const utilidadRepuestos = Math.max(0, totalProductos - costoProductosVendidos);

  const totalServicios = filteredSales.reduce((acc, s) => {
    const svcs = s.services || [];
    const sub = svcs.reduce((sum, srv) => sum + Number(srv.price || 0), 0);
    return acc + sub;
  }, 0);

  // Arqueo de Caja por método de pago
  const ingresosEfectivo = filteredSales.reduce((acc, s) => {
    const paid =
      s.paid_amount != null
        ? Number(s.paid_amount)
        : Math.max(0, Number(s.total || 0) - Number(s.pending_balance || 0));
    const method = (s.payment_method || "Efectivo").toLowerCase();
    if (method.includes("efectivo")) {
      return acc + paid;
    }
    if (method.includes("mixto") && s.payment_notes) {
      const match = s.payment_notes.match(/Efectivo:\s*\$\s*([\d.,]+)/i);
      if (match && match[1]) {
        const parsedCash = parseInt(match[1].replace(/\D/g, ""), 10) || 0;
        return acc + parsedCash;
      }
    }
    return acc;
  }, 0);

  const ingresosDigitales = filteredSales.reduce((acc, s) => {
    const paid =
      s.paid_amount != null
        ? Number(s.paid_amount)
        : Math.max(0, Number(s.total || 0) - Number(s.pending_balance || 0));
    const method = (s.payment_method || "Efectivo").toLowerCase();
    if (method.includes("transferencia") || method.includes("nequi") || method.includes("tarjeta")) {
      return acc + paid;
    }
    if (method.includes("mixto") && s.payment_notes) {
      const match = s.payment_notes.match(/Transf\/Tarjeta:\s*\$\s*([\d.,]+)/i);
      if (match && match[1]) {
        const parsedTransf = parseInt(match[1].replace(/\D/g, ""), 10) || 0;
        return acc + parsedTransf;
      }
    }
    return acc;
  }, 0);

  const totalPorCobrar = filteredSales.reduce(
    (acc, s) => acc + Number(s.pending_balance || 0),
    0
  );

  const totalGastos = filteredExpenses.reduce(
    (acc, e) => acc + Number(e.amount || 0),
    0
  );

  const gastosEnEfectivo = filteredExpenses.reduce((acc, e) => {
    const m = (e.payment_method || "Efectivo").toLowerCase();
    return m.includes("efectivo") ? acc + Number(e.amount || 0) : acc;
  }, 0);

  const efectivoNetoCaja = ingresosEfectivo - gastosEnEfectivo;

  const lowStockProducts = products.filter(
    (p) => Number(p.stock ?? 0) <= Number(p.min_stock ?? 5)
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

  const handleAddExpense = async (e) => {
    e.preventDefault();
    if (!newExpense.description.trim() || !newExpense.amount) return;

    try {
      const response = await fetch(`${API_URL}/expenses`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${token}`,
          Accept: "application/json",
        },
        body: JSON.stringify({
          description: newExpense.description.trim(),
          category: newExpense.category,
          amount: parseInt(newExpense.amount, 10) || 0,
          payment_method: newExpense.payment_method,
        }),
      });
      if (response.ok) {
        setNewExpense({
          description: "",
          category: "Insumos Taller",
          amount: "",
          payment_method: "Efectivo",
        });
        setStatusMsg("Gasto de caja registrado correctamente");
        setTimeout(() => setStatusMsg(""), 3500);
        await fetchReportData();
      }
    } catch (error) {
      console.error("Error al registrar gasto:", error);
    }
  };

  const handleDeleteExpense = async (id) => {
    if (!window.confirm("¿Deseas eliminar este registro de gasto?")) return;
    try {
      const response = await fetch(`${API_URL}/expenses/${id}`, {
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
      console.error("Error al eliminar gasto:", error);
    }
  };

  const handleSaldarVenta = async (sale) => {
    const pending = Number(sale.pending_balance || 0);
    if (pending <= 0) return;
    if (
      !window.confirm(
        `¿Confirmas saldar la cuenta pendiente de $ ${formatPrice(pending)} de ${
          sale.customer?.name || "Cliente"
        }?`
      )
    ) {
      return;
    }

    try {
      const response = await fetch(`${API_URL}/sales/${sale.id}`, {
        method: "PUT",
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${token}`,
          Accept: "application/json",
        },
        body: JSON.stringify({
          abono: pending,
          payment_notes: `${sale.payment_notes ? sale.payment_notes + " | " : ""}Saldo pagado ($ ${formatPrice(pending)})`,
        }),
      });
      if (response.ok) {
        setStatusMsg(`Cuenta de la factura #${sale.id} saldada en su totalidad`);
        setTimeout(() => setStatusMsg(""), 3500);
        await fetchReportData();
      }
    } catch (error) {
      console.error("Error al saldar cuenta:", error);
    }
  };

  return (
    <div className="min-h-screen w-full flex flex-col bg-slate-100 p-4 md:p-6 gap-5">
      {/* Encabezado */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white px-5 py-3.5 rounded-xl shadow-sm border border-slate-200">
        <div>
          <h1 className="text-xl font-extrabold text-[#023047] tracking-tight">
            REPORTES, ARQUEO DE CAJA Y CONTROL DE GASTOS
          </h1>
          <p className="text-xs text-slate-500">
            Resumen financiero de ventas, arqueo por medio de pago, gastos menores y liquidación de mecánicos
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

      {/* Tarjetas Principales de Ventas */}
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
            <span className="text-[11px] font-bold text-emerald-700">
              Utilidad Neta: $ {formatPrice(utilidadRepuestos)}
            </span>
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

      {/* Panel de Arqueo de Caja y Flujo de Efectivo */}
      <div className="bg-white rounded-xl shadow-sm border border-slate-200 p-5 flex flex-col gap-4">
        <div className="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 pb-3">
          <div className="flex items-center gap-2">
            <FaCashRegister className="text-[#fb8500] text-lg" />
            <div>
              <h2 className="text-base font-extrabold text-[#023047]">
                Arqueo de Caja por Medio de Pago y Flujo Neto
              </h2>
              <p className="text-xs text-slate-500">
                Control de efectivo físico en gaveta, pagos digitales (Nequi/Transferencia/Tarjeta), créditos y salidas de caja
              </p>
            </div>
          </div>
        </div>

        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
          <div className="p-3.5 rounded-xl bg-emerald-50 border border-emerald-200">
            <span className="text-[11px] font-bold uppercase text-emerald-800 block">
              Recaudo en Efectivo
            </span>
            <p className="text-xl font-black text-emerald-700 mt-1">
              $ {formatPrice(ingresosEfectivo)}
            </p>
          </div>

          <div className="p-3.5 rounded-xl bg-sky-50 border border-sky-200">
            <span className="text-[11px] font-bold uppercase text-sky-800 block">
              Transferencias / Tarjeta
            </span>
            <p className="text-xl font-black text-sky-900 mt-1">
              $ {formatPrice(ingresosDigitales)}
            </p>
          </div>

          <div className="p-3.5 rounded-xl bg-amber-50 border border-amber-200">
            <span className="text-[11px] font-bold uppercase text-amber-900 block">
              Cartera por Cobrar
            </span>
            <p className="text-xl font-black text-amber-800 mt-1">
              $ {formatPrice(totalPorCobrar)}
            </p>
          </div>

          <div className="p-3.5 rounded-xl bg-rose-50 border border-rose-200">
            <span className="text-[11px] font-bold uppercase text-rose-800 block">
              Salidas / Gastos ({filteredExpenses.length})
            </span>
            <p className="text-xl font-black text-rose-700 mt-1">
              - $ {formatPrice(totalGastos)}
            </p>
          </div>

          <div className="p-3.5 rounded-xl bg-[#023047] text-white shadow-sm">
            <span className="text-[11px] font-bold uppercase text-amber-300 block">
              Efectivo Neto en Caja
            </span>
            <p className="text-xl font-black text-[#ffb703] mt-1">
              $ {formatPrice(efectivoNetoCaja)}
            </p>
          </div>
        </div>
      </div>

      {/* Grilla: Mecánicos, Gastos Menores y Stock Bajo */}
      <div className="grid grid-cols-1 lg:grid-cols-12 gap-5">
        {/* Producción por Mecánico */}
        <div className="lg:col-span-5 bg-white rounded-xl shadow-sm border border-slate-200 p-5 flex flex-col gap-4">
          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-200 pb-3">
            <div>
              <h2 className="text-base font-extrabold text-[#023047]">
                Liquidación Diaria por Mecánico
              </h2>
              <p className="text-xs text-slate-500">
                Acumulado del día en servicios asignados
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
                    <th className="py-2.5 px-3">Mecánico</th>
                    <th className="py-2.5 px-3 text-right">Acumulado Día</th>
                    <th className="py-2.5 px-3 text-center">Acciones</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {workers.map((w) => (
                    <tr key={w.id} className="hover:bg-slate-50">
                      <td className="py-2.5 px-3 font-semibold text-slate-800">
                        {w.name}
                      </td>
                      <td className="py-2.5 px-3 text-right font-extrabold text-emerald-700">
                        $ {formatPrice(w.totalDia)}
                      </td>
                      <td className="py-2.5 px-3">
                        <div className="flex items-center justify-center gap-1.5">
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

        {/* Registro de Gastos Menores / Salidas de Caja */}
        <div className="lg:col-span-4 bg-white rounded-xl shadow-sm border border-slate-200 p-5 flex flex-col gap-3">
          <div className="border-b border-slate-200 pb-3 flex items-center justify-between">
            <div>
              <h2 className="text-base font-extrabold text-[#023047] flex items-center gap-1.5">
                <FaReceipt className="text-rose-500" />
                <span>Gastos Menores / Salidas de Caja</span>
              </h2>
              <p className="text-xs text-slate-500">
                Registra pagos de insumos, domicilios o gastos del día
              </p>
            </div>
          </div>

          <form onSubmit={handleAddExpense} className="grid grid-cols-1 sm:grid-cols-2 gap-2 bg-slate-50 p-3 rounded-xl border border-slate-200">
            <input
              type="text"
              placeholder="Concepto del gasto..."
              value={newExpense.description}
              onChange={(e) =>
                setNewExpense((prev) => ({ ...prev, description: e.target.value }))
              }
              className="sm:col-span-2 px-2.5 py-1.5 text-xs border border-slate-300 rounded-lg bg-white"
              required
            />
            <input
              type="number"
              min="1"
              placeholder="Valor ($ COP)"
              value={newExpense.amount}
              onChange={(e) =>
                setNewExpense((prev) => ({ ...prev, amount: e.target.value }))
              }
              className="px-2.5 py-1.5 text-xs border border-slate-300 rounded-lg bg-white font-bold"
              required
            />
            <select
              value={newExpense.payment_method}
              onChange={(e) =>
                setNewExpense((prev) => ({
                  ...prev,
                  payment_method: e.target.value,
                }))
              }
              className="px-2.5 py-1.5 text-xs border border-slate-300 rounded-lg bg-white"
            >
              <option value="Efectivo">Sale de: Efectivo</option>
              <option value="Transferencia / Nequi">Sale de: Transf./Nequi</option>
            </select>
            <button
              type="submit"
              className="sm:col-span-2 py-1.5 px-3 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold flex items-center justify-center gap-1.5 cursor-pointer transition"
            >
              <FaPlus /> Registrar Gasto de Caja
            </button>
          </form>

          <div className="flex-1 overflow-y-auto max-h-52 divide-y divide-slate-100">
            {filteredExpenses.length === 0 ? (
              <div className="py-6 text-center text-slate-400 text-xs">
                No hay gastos registrados en este período.
              </div>
            ) : (
              filteredExpenses.map((exp) => (
                <div
                  key={exp.id}
                  className="py-2 px-1 flex items-center justify-between text-xs"
                >
                  <div>
                    <p className="font-semibold text-slate-800">{exp.description}</p>
                    <span className="text-[11px] text-slate-400">
                      {exp.payment_method} • {formatDateTime(exp.created_at || exp.expense_date)}
                    </span>
                  </div>
                  <div className="flex items-center gap-2">
                    <span className="font-extrabold text-rose-600">
                      - $ {formatPrice(exp.amount)}
                    </span>
                    <button
                      type="button"
                      onClick={() => handleDeleteExpense(exp.id)}
                      className="text-slate-400 hover:text-rose-600 cursor-pointer"
                      title="Eliminar gasto"
                    >
                      <FaTrash />
                    </button>
                  </div>
                </div>
              ))
            )}
          </div>
        </div>

        {/* Alertas de Stock Bajo */}
        <div className="lg:col-span-3 bg-white rounded-xl shadow-sm border border-slate-200 p-5 flex flex-col gap-3">
          <div className="border-b border-slate-200 pb-3">
            <h2 className="text-base font-extrabold text-[#023047]">
              Alertas de Stock ({lowStockProducts.length})
            </h2>
            <p className="text-xs text-slate-500">
              Productos con ≤ 5 unidades
            </p>
          </div>

          <div className="flex-1 overflow-y-auto max-h-64 divide-y divide-slate-100">
            {lowStockProducts.length === 0 ? (
              <div className="py-8 text-center text-emerald-600 text-xs font-medium">
                Todo el inventario tiene niveles óptimos de stock.
              </div>
            ) : (
              lowStockProducts.map((p) => (
                <div
                  key={p.id}
                  className="py-2.5 px-2 flex items-center justify-between text-xs"
                >
                  <div>
                    <p className="font-semibold text-slate-800">{p.name}</p>
                    <span className="text-[11px] text-slate-400">
                      #{p.id} • $ {formatPrice(p.price)}
                    </span>
                  </div>
                  <span
                    className={`px-2 py-0.5 rounded-full text-[11px] font-bold ${
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
              Haz clic en cualquier número de factura para previsualizar el soporte, enviar por WhatsApp o saldar cuentas por cobrar
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
                  <th className="py-3 px-4">Pago / Estado</th>
                  <th className="py-3 px-4">Fecha</th>
                  <th className="py-3 px-4 text-right">Total</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {filteredSales.map((sale) => {
                  const pending = Number(sale.pending_balance || 0);
                  return (
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
                        <div className="flex items-center gap-1.5 flex-wrap">
                          <span className="px-2 py-0.5 rounded bg-slate-100 text-slate-700 text-xs font-semibold">
                            {sale.payment_method || "Efectivo"}
                          </span>
                          {pending > 0 ? (
                            <button
                              type="button"
                              onClick={() => handleSaldarVenta(sale)}
                              title="Clic para saldar esta cuenta por cobrar"
                              className="px-2 py-0.5 rounded-full bg-rose-100 hover:bg-rose-200 text-rose-700 border border-rose-300 text-[11px] font-extrabold flex items-center gap-1 cursor-pointer transition"
                            >
                              <FaHandHoldingUsd />
                              <span>Debe: $ {formatPrice(pending)} (Saldar)</span>
                            </button>
                          ) : (
                            <span className="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-300 text-[11px] font-bold">
                              Pagado
                            </span>
                          )}
                        </div>
                      </td>
                      <td className="py-3 px-4 text-slate-500">
                        {formatDateTime(sale.created_at)}
                      </td>
                      <td className="py-3 px-4 text-right font-extrabold text-[#023047]">
                        $ {formatPrice(sale.total)}
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </div>
  );
};

export default Report;