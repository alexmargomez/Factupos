import { useEffect, useState, useCallback } from "react";
import DateTimeDisplay from "../components/DateTimeDisplay";
import Ready from "../components/Ready";
import Modalmini from "../components/Modalmini";
import {
  FaBoxOpen,
  FaPlus,
  FaMinus,
  FaSearch,
  FaTruck,
  FaBarcode,
  FaCoins,
  FaChartLine,
  FaTrash,
  FaCartPlus,
} from "react-icons/fa";

const Inventory = () => {
  const API_URL = import.meta.env.VITE_API_URL;
  const token = localStorage.getItem("authToken");

  const [activeView, setActiveView] = useState("catalog"); // 'catalog' | 'suppliers'
  const [showModal, setShowModal] = useState(false);
  const [products, setProducts] = useState([]);
  const [suppliers, setSuppliers] = useState([]);

  // Nuevo producto
  const [sku, setSku] = useState("");
  const [name, setName] = useState("");
  const [costPrice, setCostPrice] = useState("");
  const [price, setPrice] = useState("");
  const [stock, setStock] = useState("");
  const [minStock, setMinStock] = useState("5");
  const [supplierId, setSupplierId] = useState("");

  // Buscador y estados
  const [searchTerm, setSearchTerm] = useState("");
  const [errorMsg, setErrorMsg] = useState("");
  const [isSaving, setIsSaving] = useState(false);

  // Modal de Entrada de Compra / Reabastecimiento
  const [purchaseModal, setPurchaseModal] = useState({
    open: false,
    product: null,
    quantity: "",
    cost_price: "",
    price: "",
    supplier_id: "",
    invoice_ref: "",
  });

  // Formulario nuevo proveedor
  const [newSupplier, setNewSupplier] = useState({
    name: "",
    nit: "",
    phone: "",
    email: "",
    contact_name: "",
  });

  const fetchInventoryData = useCallback(async () => {
    try {
      const headers = {
        Authorization: `Bearer ${token}`,
        Accept: "application/json",
      };
      const [prodRes, supRes] = await Promise.all([
        fetch(`${API_URL}/products`, { headers }),
        fetch(`${API_URL}/suppliers`, { headers }).catch(() => ({ ok: false })),
      ]);

      if (prodRes.ok) {
        const resultproducts = await prodRes.json();
        setProducts(Array.isArray(resultproducts) ? resultproducts : []);
      }
      if (supRes && supRes.ok) {
        const resultSuppliers = await supRes.json();
        setSuppliers(Array.isArray(resultSuppliers) ? resultSuppliers : []);
      }
    } catch (error) {
      console.error("Error fetching inventory data:", error);
    }
  }, [API_URL, token]);

  useEffect(() => {
    fetchInventoryData();
  }, [fetchInventoryData]);

  const handleAddProducts = async (event) => {
    event.preventDefault();
    if (!name.trim() || price === "" || stock === "") {
      setErrorMsg("Por favor completa el nombre, precio de venta y stock inicial.");
      return;
    }
    if (Number(price) < 0 || Number(stock) < 0 || Number(costPrice || 0) < 0) {
      setErrorMsg("Los valores de costo, precio y stock no pueden ser negativos.");
      return;
    }

    setIsSaving(true);
    setErrorMsg("");
    try {
      const response = await fetch(`${API_URL}/products`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${token}`,
        },
        body: JSON.stringify({
          sku: sku.trim() || null,
          name: name.trim(),
          cost_price: Number(costPrice || 0),
          price: Number(price),
          min_stock: Number(minStock || 5),
          supplier_id: supplierId ? Number(supplierId) : null,
          stock: Number(stock),
        }),
      });

      if (!response.ok) {
        throw new Error("No se pudo crear el producto.");
      }

      const newProduct = await response.json();

      // Asegurar registro en /inventory por compatibilidad
      await fetch(`${API_URL}/inventory`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${token}`,
        },
        body: JSON.stringify({
          stock: Number(stock),
          product_id: newProduct.id,
        }),
      });

      await fetchInventoryData();
      setSku("");
      setName("");
      setCostPrice("");
      setPrice("");
      setStock("");
      setMinStock("5");
      setSupplierId("");
      setShowModal(true);
    } catch (error) {
      console.error("Error al crear producto:", error);
      setErrorMsg("Error al guardar el producto. Verifica los datos.");
    } finally {
      setIsSaving(false);
    }
  };

  const adjustStock = async (product, delta) => {
    const currentStock = Number(product.stock) || 0;
    const nextStock = currentStock + delta;
    if (nextStock < 0) return;

    try {
      const updateResponse = await fetch(`${API_URL}/inventory/${product.id}`, {
        method: "PUT",
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${token}`,
        },
        body: JSON.stringify({ stock: nextStock }),
      });
      if (updateResponse.ok) {
        setProducts((prev) =>
          prev.map((p) => (p.id === product.id ? { ...p, stock: nextStock } : p))
        );
      }
    } catch (error) {
      console.error("Error actualizando stock:", error);
    }
  };

  const openPurchaseModal = (product) => {
    setPurchaseModal({
      open: true,
      product,
      quantity: "",
      cost_price: String(product.cost_price ?? 0),
      price: String(product.price ?? 0),
      supplier_id: product.supplier_id ? String(product.supplier_id) : "",
      invoice_ref: "",
    });
  };

  const handleRegisterPurchase = async (e) => {
    e.preventDefault();
    if (!purchaseModal.product) return;
    const qty = parseInt(purchaseModal.quantity, 10) || 0;
    if (qty <= 0) {
      alert("Ingresa una cantidad de entrada mayor a 0.");
      return;
    }

    try {
      const response = await fetch(
        `${API_URL}/products/${purchaseModal.product.id}/purchase`,
        {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            Authorization: `Bearer ${token}`,
            Accept: "application/json",
          },
          body: JSON.stringify({
            quantity: qty,
            cost_price: Number(purchaseModal.cost_price || 0),
            price: Number(purchaseModal.price || 0),
            supplier_id: purchaseModal.supplier_id
              ? Number(purchaseModal.supplier_id)
              : null,
            invoice_ref: purchaseModal.invoice_ref.trim() || null,
          }),
        }
      );

      if (!response.ok) {
        throw new Error("No se pudo registrar la compra de mercancía.");
      }

      setPurchaseModal({
        open: false,
        product: null,
        quantity: "",
        cost_price: "",
        price: "",
        supplier_id: "",
        invoice_ref: "",
      });
      await fetchInventoryData();
      setShowModal(true);
    } catch (err) {
      alert(err.message);
    }
  };

  const handleCreateSupplier = async (e) => {
    e.preventDefault();
    if (!newSupplier.name.trim()) return;
    try {
      const response = await fetch(`${API_URL}/suppliers`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${token}`,
          Accept: "application/json",
        },
        body: JSON.stringify({
          name: newSupplier.name.trim(),
          nit: newSupplier.nit.trim() || null,
          phone: newSupplier.phone.trim() || null,
          email: newSupplier.email.trim() || null,
          contact_name: newSupplier.contact_name.trim() || null,
        }),
      });
      if (response.ok) {
        setNewSupplier({
          name: "",
          nit: "",
          phone: "",
          email: "",
          contact_name: "",
        });
        await fetchInventoryData();
        setShowModal(true);
      }
    } catch (error) {
      console.error("Error creando proveedor:", error);
    }
  };

  const handleDeleteSupplier = async (id) => {
    if (!window.confirm("¿Deseas eliminar este proveedor?")) return;
    try {
      const response = await fetch(`${API_URL}/suppliers/${id}`, {
        method: "DELETE",
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: "application/json",
        },
      });
      if (response.ok) {
        await fetchInventoryData();
      }
    } catch (error) {
      console.error("Error eliminando proveedor:", error);
    }
  };

  const formatPrice = (val) => {
    return (Number(val) || 0).toLocaleString("es-CO");
  };

  // Valorización del Inventario
  const totalCostoBodega = products.reduce(
    (acc, p) => acc + (Number(p.stock) || 0) * (Number(p.cost_price) || 0),
    0
  );
  const totalVentaBodega = products.reduce(
    (acc, p) => acc + (Number(p.stock) || 0) * (Number(p.price) || 0),
    0
  );
  const utilidadPotencial = Math.max(0, totalVentaBodega - totalCostoBodega);

  const filteredProducts = products.filter((p) => {
    const term = searchTerm.toLowerCase().trim();
    if (!term) return true;
    return (
      p.name?.toLowerCase().includes(term) ||
      p.sku?.toLowerCase().includes(term) ||
      p.supplier?.name?.toLowerCase().includes(term) ||
      String(p.id).includes(term)
    );
  });

  return (
    <div className="min-h-screen w-full flex flex-col bg-slate-100 p-4 md:p-6 gap-4">
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
          <h1 className="text-2xl font-extrabold text-[#023047] tracking-tight flex items-center gap-2">
            <FaBoxOpen className="text-[#fb8500]" />
            <span>INVENTARIO VALORIZADO, SKU Y PROVEEDORES</span>
          </h1>
          <p className="text-xs sm:text-sm text-slate-500">
            Control de costos de compra, márgenes de utilidad, códigos de barras (SKU) y entradas de proveedores
          </p>
        </div>

        <div className="flex flex-wrap items-center gap-3">
          <div className="flex bg-white p-1 rounded-xl border border-slate-200 shadow-2xs">
            <button
              type="button"
              onClick={() => setActiveView("catalog")}
              className={`px-3.5 py-1.5 rounded-lg text-xs font-extrabold flex items-center gap-1.5 transition cursor-pointer ${
                activeView === "catalog"
                  ? "bg-[#023047] text-white"
                  : "text-slate-600 hover:bg-slate-100"
              }`}
            >
              <FaBoxOpen />
              <span>Catálogo ({products.length})</span>
            </button>
            <button
              type="button"
              onClick={() => setActiveView("suppliers")}
              className={`px-3.5 py-1.5 rounded-lg text-xs font-extrabold flex items-center gap-1.5 transition cursor-pointer ${
                activeView === "suppliers"
                  ? "bg-[#023047] text-white"
                  : "text-slate-600 hover:bg-slate-100"
              }`}
            >
              <FaTruck />
              <span>Proveedores ({suppliers.length})</span>
            </button>
          </div>
          <DateTimeDisplay />
        </div>
      </div>

      {/* Tarjetas de Valorización del Inventario */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div className="bg-white p-4 rounded-xl shadow-sm border border-slate-200 flex items-center gap-3.5">
          <div className="w-11 h-11 rounded-xl bg-slate-800 text-amber-400 flex items-center justify-center text-xl shrink-0">
            <FaCoins />
          </div>
          <div>
            <span className="text-[11px] font-bold uppercase text-slate-400 block">
              Capital en Bodega (Costo)
            </span>
            <p className="text-xl font-black text-slate-800">
              $ {formatPrice(totalCostoBodega)}
            </p>
          </div>
        </div>

        <div className="bg-white p-4 rounded-xl shadow-sm border border-slate-200 flex items-center gap-3.5">
          <div className="w-11 h-11 rounded-xl bg-sky-100 text-[#023047] flex items-center justify-center text-xl shrink-0">
            <FaBoxOpen />
          </div>
          <div>
            <span className="text-[11px] font-bold uppercase text-slate-400 block">
              Valor Comercial (Venta)
            </span>
            <p className="text-xl font-black text-[#023047]">
              $ {formatPrice(totalVentaBodega)}
            </p>
          </div>
        </div>

        <div className="bg-white p-4 rounded-xl shadow-sm border border-slate-200 flex items-center gap-3.5">
          <div className="w-11 h-11 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-xl shrink-0">
            <FaChartLine />
          </div>
          <div>
            <span className="text-[11px] font-bold uppercase text-slate-400 block">
              Utilidad Potencial Estimada
            </span>
            <p className="text-xl font-black text-emerald-700">
              $ {formatPrice(utilidadPotencial)}
            </p>
          </div>
        </div>

        <div className="bg-white p-4 rounded-xl shadow-sm border border-slate-200 flex items-center gap-3.5">
          <div className="w-11 h-11 rounded-xl bg-amber-100 text-amber-800 flex items-center justify-center text-xl shrink-0">
            <FaTruck />
          </div>
          <div>
            <span className="text-[11px] font-bold uppercase text-slate-400 block">
              Proveedores Registrados
            </span>
            <p className="text-xl font-black text-slate-800">
              {suppliers.length}
            </p>
          </div>
        </div>
      </div>

      {activeView === "catalog" ? (
        <div
          className={`flex-1 bg-white rounded-2xl shadow-sm border border-slate-200 border-t-4 border-t-[#ffb703] p-5 flex flex-col gap-6 ${
            showModal ? "animate-border" : ""
          }`}
        >
          {/* Formulario de Creación */}
          <section className="bg-slate-50 border border-slate-200 rounded-xl p-4">
            <h2 className="text-sm font-bold uppercase tracking-wider text-[#023047] mb-3 flex items-center gap-2">
              <FaBarcode className="text-[#219ebc]" />
              <span>Registrar Nuevo Repuesto / Producto</span>
            </h2>
            {errorMsg && (
              <div className="mb-3 p-2.5 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold">
                {errorMsg}
              </div>
            )}
            <form
              onSubmit={handleAddProducts}
              className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3"
            >
              <div>
                <label className="block text-[11px] font-bold uppercase text-slate-500 mb-1">
                  Código de Barras / SKU (Opcional)
                </label>
                <input
                  type="text"
                  value={sku}
                  placeholder="Ej: 770123456 o REF-01"
                  className="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-[#219ebc]"
                  onChange={(e) => setSku(e.target.value)}
                />
              </div>

              <div className="sm:col-span-2">
                <label className="block text-[11px] font-bold uppercase text-slate-500 mb-1">
                  Nombre del Producto o Repuesto *
                </label>
                <input
                  type="text"
                  value={name}
                  placeholder="Ej: Pastillas de freno delanteras NMAX"
                  className="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-[#219ebc]"
                  onChange={(e) => setName(e.target.value)}
                />
              </div>

              <div>
                <label className="block text-[11px] font-bold uppercase text-slate-500 mb-1">
                  Proveedor
                </label>
                <select
                  value={supplierId}
                  onChange={(e) => setSupplierId(e.target.value)}
                  className="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-[#219ebc]"
                >
                  <option value="">Sin proveedor específico</option>
                  {suppliers.map((sup) => (
                    <option key={sup.id} value={sup.id}>
                      {sup.name}
                    </option>
                  ))}
                </select>
              </div>

              <div>
                <label className="block text-[11px] font-bold uppercase text-slate-500 mb-1">
                  Costo de Compra ($ COP)
                </label>
                <input
                  type="number"
                  min="0"
                  value={costPrice}
                  placeholder="Ej: 22000"
                  className="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-[#219ebc]"
                  onChange={(e) => setCostPrice(e.target.value)}
                />
              </div>

              <div>
                <label className="block text-[11px] font-bold uppercase text-slate-500 mb-1">
                  Precio de Venta ($ COP) *
                </label>
                <input
                  type="number"
                  min="0"
                  value={price}
                  placeholder="Ej: 35000"
                  className="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl bg-white font-bold focus:outline-none focus:ring-2 focus:ring-[#219ebc]"
                  onChange={(e) => setPrice(e.target.value)}
                />
              </div>

              <div className="grid grid-cols-2 gap-2">
                <div>
                  <label className="block text-[11px] font-bold uppercase text-slate-500 mb-1">
                    Stock Inicial *
                  </label>
                  <input
                    type="number"
                    min="0"
                    value={stock}
                    placeholder="Cant."
                    className="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-[#219ebc]"
                    onChange={(e) => setStock(e.target.value)}
                  />
                </div>
                <div>
                  <label className="block text-[11px] font-bold uppercase text-slate-500 mb-1">
                    Alerta Mín.
                  </label>
                  <input
                    type="number"
                    min="0"
                    value={minStock}
                    placeholder="5"
                    className="w-full px-3 py-2 text-sm border border-slate-300 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-[#219ebc]"
                    onChange={(e) => setMinStock(e.target.value)}
                  />
                </div>
              </div>

              <div className="flex items-end">
                <button
                  type="submit"
                  disabled={isSaving}
                  className="button-Date w-full px-5 py-2.5 rounded-xl font-bold text-sm shadow-sm flex items-center justify-center gap-2 cursor-pointer"
                >
                  <FaPlus />
                  <span>{isSaving ? "Guardando..." : "Guardar Producto"}</span>
                </button>
              </div>
            </form>
            <Ready
              show={showModal}
              onClose={() => setShowModal(false)}
              Date="REGISTRADO"
            />
          </section>

          {/* Tabla de Inventario con Buscador */}
          <section className="flex-1 flex flex-col">
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
              <h2 className="text-base font-bold text-[#023047]">
                Catálogo Valorizado ({filteredProducts.length} productos)
              </h2>
              <div className="relative w-full sm:w-96">
                <FaSearch className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
                <input
                  type="text"
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                  placeholder="Buscar por SKU, código, nombre o proveedor..."
                  className="w-full pl-10 pr-4 py-2 text-sm border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#219ebc]"
                />
              </div>
            </div>

            <div className="overflow-x-auto border border-slate-200 rounded-xl">
              <table className="w-full text-left border-collapse">
                <thead>
                  <tr className="bg-[#023047] text-white text-xs font-bold uppercase">
                    <th className="py-3 px-4">Cód. / SKU</th>
                    <th className="py-3 px-4">Producto / Proveedor</th>
                    <th className="py-3 px-4 text-right">Costo Compra</th>
                    <th className="py-3 px-4 text-right">Precio Venta</th>
                    <th className="py-3 px-4 text-center">Margen</th>
                    <th className="py-3 px-4 text-center">Existencias</th>
                    <th className="py-3 px-4 text-center">Abastecimiento / Ajuste</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-200 text-sm">
                  {filteredProducts.length === 0 ? (
                    <tr>
                      <td colSpan="7" className="py-8 text-center text-slate-400">
                        No hay productos que coincidan con la búsqueda.
                      </td>
                    </tr>
                  ) : (
                    filteredProducts.map((product) => {
                      const currentStock = Number(product.stock) || 0;
                      const minAlert = Number(product.min_stock ?? 5);
                      const cost = Number(product.cost_price) || 0;
                      const salePrice = Number(product.price) || 0;
                      const profit = salePrice - cost;
                      const marginPct =
                        salePrice > 0 ? Math.round((profit / salePrice) * 100) : 0;

                      return (
                        <tr key={product.id} className="hover:bg-slate-50 transition">
                          <td className="py-3 px-4">
                            <span className="font-mono text-xs font-bold text-slate-600 block">
                              #{product.id}
                            </span>
                            {product.sku && (
                              <span className="inline-block mt-0.5 bg-slate-100 text-slate-700 border border-slate-300 px-1.5 py-0.5 rounded font-mono text-[11px]">
                                {product.sku}
                              </span>
                            )}
                          </td>
                          <td className="py-3 px-4">
                            <p className="font-semibold text-slate-800">
                              {product.name}
                            </p>
                            {product.supplier?.name && (
                              <span className="text-xs text-slate-400 flex items-center gap-1 mt-0.5">
                                <FaTruck className="text-[10px]" />{" "}
                                {product.supplier.name}
                              </span>
                            )}
                          </td>
                          <td className="py-3 px-4 text-right text-slate-600 font-medium">
                            $ {formatPrice(cost)}
                          </td>
                          <td className="py-3 px-4 text-right font-extrabold text-[#023047]">
                            $ {formatPrice(salePrice)}
                          </td>
                          <td className="py-3 px-4 text-center">
                            {cost > 0 ? (
                              <span
                                className={`inline-block px-2 py-0.5 rounded text-xs font-bold ${
                                  profit > 0
                                    ? "bg-emerald-50 text-emerald-700 border border-emerald-200"
                                    : "bg-rose-50 text-rose-700"
                                }`}
                              >
                                +$ {formatPrice(profit)} ({marginPct}%)
                              </span>
                            ) : (
                              <span className="text-xs text-slate-400">—</span>
                            )}
                          </td>
                          <td className="py-3 px-4 text-center">
                            <span
                              className={`inline-block px-3 py-1 rounded-full text-xs font-extrabold ${
                                currentStock > minAlert
                                  ? "bg-emerald-100 text-emerald-800"
                                  : currentStock > 0
                                  ? "bg-amber-100 text-amber-800"
                                  : "bg-rose-100 text-rose-700"
                              }`}
                            >
                              {currentStock} und.
                            </span>
                          </td>
                          <td className="py-3 px-4">
                            <div className="flex items-center justify-center gap-1.5 flex-wrap">
                              <button
                                type="button"
                                onClick={() => openPurchaseModal(product)}
                                className="bg-[#023047] hover:bg-[#219ebc] text-white px-2.5 py-1.5 rounded-lg text-xs font-bold inline-flex items-center gap-1 transition cursor-pointer"
                                title="Registrar entrada de factura de compra"
                              >
                                <FaCartPlus /> Compra
                              </button>
                              <button
                                type="button"
                                onClick={() => adjustStock(product, 1)}
                                className="verde px-2.5 py-1.5 rounded-lg text-xs font-bold inline-flex items-center gap-1 shadow-2xs"
                                title="Sumar 1 unidad rápida"
                              >
                                <FaPlus />
                              </button>
                              <button
                                type="button"
                                disabled={currentStock <= 0}
                                onClick={() => adjustStock(product, -1)}
                                className="rojo px-2.5 py-1.5 rounded-lg text-xs font-bold inline-flex items-center gap-1 shadow-2xs disabled:opacity-40"
                                title="Restar 1 unidad rápida"
                              >
                                <FaMinus />
                              </button>
                            </div>
                          </td>
                        </tr>
                      );
                    })
                  )}
                </tbody>
              </table>
            </div>
          </section>

          {/* Modal para Registrar Entrada de Compra */}
          <Modalmini
            show={purchaseModal.open}
            onClose={() =>
              setPurchaseModal({
                open: false,
                product: null,
                quantity: "",
                cost_price: "",
                price: "",
                supplier_id: "",
                invoice_ref: "",
              })
            }
          >
            {purchaseModal.product && (
              <form
                onSubmit={handleRegisterPurchase}
                className="flex flex-col w-full p-5 space-y-4 text-left"
              >
                <div className="border-b border-slate-200 pb-3">
                  <h2 className="text-lg font-extrabold text-[#023047]">
                    Entrada de Mercancía / Compra
                  </h2>
                  <p className="text-xs text-slate-500">
                    Producto:{" "}
                    <strong className="text-slate-800">
                      {purchaseModal.product.name}
                    </strong>{" "}
                    (Stock actual: {purchaseModal.product.stock ?? 0} und.)
                  </p>
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <div>
                    <label className="block text-xs font-bold uppercase text-slate-600 mb-1">
                      Cantidad Comprada (Unidades) *
                    </label>
                    <input
                      type="number"
                      min="1"
                      required
                      value={purchaseModal.quantity}
                      onChange={(e) =>
                        setPurchaseModal((prev) => ({
                          ...prev,
                          quantity: e.target.value,
                        }))
                      }
                      placeholder="Ej: 12"
                      className="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm font-bold"
                    />
                  </div>

                  <div>
                    <label className="block text-xs font-bold uppercase text-slate-600 mb-1">
                      Proveedor
                    </label>
                    <select
                      value={purchaseModal.supplier_id}
                      onChange={(e) =>
                        setPurchaseModal((prev) => ({
                          ...prev,
                          supplier_id: e.target.value,
                        }))
                      }
                      className="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm bg-white"
                    >
                      <option value="">Sin especificar</option>
                      {suppliers.map((s) => (
                        <option key={s.id} value={s.id}>
                          {s.name}
                        </option>
                      ))}
                    </select>
                  </div>

                  <div>
                    <label className="block text-xs font-bold uppercase text-slate-600 mb-1">
                      Costo de Compra Unitario ($)
                    </label>
                    <input
                      type="number"
                      min="0"
                      value={purchaseModal.cost_price}
                      onChange={(e) =>
                        setPurchaseModal((prev) => ({
                          ...prev,
                          cost_price: e.target.value,
                        }))
                      }
                      className="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm"
                    />
                  </div>

                  <div>
                    <label className="block text-xs font-bold uppercase text-slate-600 mb-1">
                      Precio de Venta Unitario ($)
                    </label>
                    <input
                      type="number"
                      min="0"
                      value={purchaseModal.price}
                      onChange={(e) =>
                        setPurchaseModal((prev) => ({
                          ...prev,
                          price: e.target.value,
                        }))
                      }
                      className="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm font-bold"
                    />
                  </div>

                  <div className="sm:col-span-2">
                    <label className="block text-xs font-bold uppercase text-slate-600 mb-1">
                      N° Factura / Remisión de Proveedor (Opcional)
                    </label>
                    <input
                      type="text"
                      value={purchaseModal.invoice_ref}
                      onChange={(e) =>
                        setPurchaseModal((prev) => ({
                          ...prev,
                          invoice_ref: e.target.value,
                        }))
                      }
                      placeholder="Ej: FAC-9021 Distribuidora Yamaha"
                      className="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm"
                    />
                  </div>
                </div>

                <div className="flex justify-end gap-2 pt-3 border-t border-slate-200">
                  <button
                    type="button"
                    className="rojo"
                    onClick={() =>
                      setPurchaseModal({
                        open: false,
                        product: null,
                        quantity: "",
                        cost_price: "",
                        price: "",
                        supplier_id: "",
                        invoice_ref: "",
                      })
                    }
                  >
                    Cancelar
                  </button>
                  <button type="submit" className="blue">
                    Ingresar al Inventario
                  </button>
                </div>
              </form>
            )}
          </Modalmini>
        </div>
      ) : (
        /* Vista de Directorio de Proveedores */
        <div className="flex-1 bg-white rounded-2xl shadow-sm border border-slate-200 border-t-4 border-t-[#219ebc] p-5 flex flex-col gap-6">
          <section className="bg-slate-50 border border-slate-200 rounded-xl p-4">
            <h2 className="text-sm font-bold uppercase tracking-wider text-[#023047] mb-3 flex items-center gap-2">
              <FaTruck className="text-[#fb8500]" />
              <span>Registrar Nuevo Proveedor de Repuestos / Insumos</span>
            </h2>
            <form
              onSubmit={handleCreateSupplier}
              className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3"
            >
              <input
                type="text"
                required
                placeholder="Razón Social / Proveedor *"
                value={newSupplier.name}
                onChange={(e) =>
                  setNewSupplier((prev) => ({ ...prev, name: e.target.value }))
                }
                className="lg:col-span-2 px-3 py-2 text-sm border border-slate-300 rounded-xl bg-white"
              />
              <input
                type="text"
                placeholder="NIT / Documento"
                value={newSupplier.nit}
                onChange={(e) =>
                  setNewSupplier((prev) => ({ ...prev, nit: e.target.value }))
                }
                className="px-3 py-2 text-sm border border-slate-300 rounded-xl bg-white"
              />
              <input
                type="text"
                placeholder="Teléfono / WhatsApp"
                value={newSupplier.phone}
                onChange={(e) =>
                  setNewSupplier((prev) => ({ ...prev, phone: e.target.value }))
                }
                className="px-3 py-2 text-sm border border-slate-300 rounded-xl bg-white"
              />
              <input
                type="text"
                placeholder="Nombre Asesor / Contacto"
                value={newSupplier.contact_name}
                onChange={(e) =>
                  setNewSupplier((prev) => ({
                    ...prev,
                    contact_name: e.target.value,
                  }))
                }
                className="px-3 py-2 text-sm border border-slate-300 rounded-xl bg-white"
              />
              <button
                type="submit"
                className="button-Date px-4 py-2 rounded-xl font-bold text-sm flex items-center justify-center gap-2 cursor-pointer"
              >
                <FaPlus /> Guardar
              </button>
            </form>
          </section>

          <section className="flex-1">
            <div className="overflow-x-auto border border-slate-200 rounded-xl">
              <table className="w-full text-left border-collapse text-sm">
                <thead>
                  <tr className="bg-[#023047] text-white text-xs font-bold uppercase">
                    <th className="py-3 px-4">ID</th>
                    <th className="py-3 px-4">Proveedor</th>
                    <th className="py-3 px-4">NIT</th>
                    <th className="py-3 px-4">Teléfono</th>
                    <th className="py-3 px-4">Contacto / Asesor</th>
                    <th className="py-3 px-4 text-center">Acciones</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-200">
                  {suppliers.length === 0 ? (
                    <tr>
                      <td colSpan="6" className="py-8 text-center text-slate-400">
                        No hay proveedores registrados todavía.
                      </td>
                    </tr>
                  ) : (
                    suppliers.map((sup) => (
                      <tr key={sup.id} className="hover:bg-slate-50">
                        <td className="py-3 px-4 font-mono text-xs text-slate-500">
                          #{sup.id}
                        </td>
                        <td className="py-3 px-4 font-bold text-slate-800">
                          {sup.name}
                        </td>
                        <td className="py-3 px-4 text-slate-600">
                          {sup.nit || "—"}
                        </td>
                        <td className="py-3 px-4 text-slate-600">
                          {sup.phone || "—"}
                        </td>
                        <td className="py-3 px-4 text-slate-600">
                          {sup.contact_name || "—"}
                        </td>
                        <td className="py-3 px-4 text-center">
                          <button
                            type="button"
                            onClick={() => handleDeleteSupplier(sup.id)}
                            className="rojo px-3 py-1.5 rounded-lg text-xs font-bold inline-flex items-center gap-1"
                          >
                            <FaTrash /> Eliminar
                          </button>
                        </td>
                      </tr>
                    ))
                  )}
                </tbody>
              </table>
            </div>
          </section>
        </div>
      )}
    </div>
  );
};

export default Inventory;
