import { useEffect, useState } from "react";
import DateTimeDisplay from "../components/DateTimeDisplay";
import Ready from "../components/Ready";
import { FaBoxOpen, FaPlus, FaMinus, FaSearch } from "react-icons/fa";

const Inventory = () => {
  const API_URL = import.meta.env.VITE_API_URL;
  const [showModal, setShowModal] = useState(false);
  const [products, setProducts] = useState([]);
  const token = localStorage.getItem("authToken");
  const [name, setName] = useState("");
  const [price, setPrice] = useState("");
  const [stock, setStock] = useState("");
  const [searchTerm, setSearchTerm] = useState("");
  const [errorMsg, setErrorMsg] = useState("");
  const [isSaving, setIsSaving] = useState(false);

  const fetchProducts = async () => {
    try {
      const response = await fetch(`${API_URL}/products`, {
        headers: {
          Authorization: `Bearer ${token}`,
        },
      });
      if (!response.ok) {
        throw new Error(`Error fetching products: ${response.statusText}`);
      }
      const resultproducts = await response.json();
      setProducts(Array.isArray(resultproducts) ? resultproducts : []);
    } catch (error) {
      console.error("Error fetching products:", error);
    }
  };

  const addProduct = async (productName, productPrice, productStock) => {
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
          name: productName.trim(),
          price: Number(productPrice),
          stock: Number(productStock),
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
          stock: Number(productStock),
          product_id: newProduct.id,
        }),
      });

      await fetchProducts();
      setName("");
      setPrice("");
      setStock("");
      setShowModal(true);
    } catch (error) {
      console.error("Error al crear producto:", error);
      setErrorMsg("Error al guardar el producto. Verifica los datos.");
    } finally {
      setIsSaving(false);
    }
  };

  const handleAddProducts = (event) => {
    event.preventDefault();
    if (!name.trim() || price === "" || stock === "") {
      setErrorMsg("Por favor completa el nombre, precio y stock inicial.");
      return;
    }
    if (Number(price) < 0 || Number(stock) < 0) {
      setErrorMsg("El precio y el stock no pueden ser negativos.");
      return;
    }
    addProduct(name, price, stock);
  };

  useEffect(() => {
    fetchProducts();
  }, [token]);

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

  const formatPrice = (val) => {
    return (Number(val) || 0).toLocaleString("es-CO");
  };

  const filteredProducts = products.filter((p) => {
    const term = searchTerm.toLowerCase().trim();
    if (!term) return true;
    return (
      p.name?.toLowerCase().includes(term) || String(p.id).includes(term)
    );
  });

  return (
    <div className="min-h-screen w-full flex flex-col bg-slate-100 p-4 md:p-6">
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-4">
        <div>
          <h1 className="text-2xl font-extrabold text-[#023047] tracking-tight flex items-center gap-2">
            <FaBoxOpen className="text-[#fb8500]" />
            <span>GESTIÓN DE INVENTARIO</span>
          </h1>
          <p className="text-sm text-slate-500">
            Registra nuevos repuestos/productos y ajusta las existencias disponibles
          </p>
        </div>
        <DateTimeDisplay />
      </div>

      <div
        className={`flex-1 bg-white rounded-2xl shadow-sm border border-slate-200 border-t-4 border-t-[#ffb703] p-5 flex flex-col gap-6 ${
          showModal ? "animate-border" : ""
        }`}
      >
        {/* Formulario de Creación */}
        <section className="bg-slate-50 border border-slate-200 rounded-xl p-4">
          <h2 className="text-sm font-bold uppercase tracking-wider text-[#023047] mb-3">
            Registrar Nuevo Producto
          </h2>
          {errorMsg && (
            <div className="mb-3 p-2.5 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold">
              {errorMsg}
            </div>
          )}
          <form
            onSubmit={handleAddProducts}
            className="grid grid-cols-1 sm:grid-cols-4 gap-3"
          >
            <input
              type="text"
              value={name}
              placeholder="Nombre del producto o repuesto"
              className="w-full px-3.5 py-2.5 text-sm border border-slate-300 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-[#219ebc]"
              onChange={(e) => setName(e.target.value)}
            />
            <input
              type="number"
              min="0"
              value={price}
              placeholder="Precio de venta ($ COP)"
              className="w-full px-3.5 py-2.5 text-sm border border-slate-300 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-[#219ebc]"
              onChange={(e) => setPrice(e.target.value)}
            />
            <input
              type="number"
              min="0"
              value={stock}
              placeholder="Stock inicial (Unidades)"
              className="w-full px-3.5 py-2.5 text-sm border border-slate-300 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-[#219ebc]"
              onChange={(e) => setStock(e.target.value)}
            />
            <button
              type="submit"
              disabled={isSaving}
              className="button-Date px-5 py-2.5 rounded-xl font-bold text-sm shadow-sm flex items-center justify-center gap-2"
            >
              <FaPlus />
              <span>{isSaving ? "Guardando..." : "Guardar Producto"}</span>
            </button>
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
              Catálogo Actual ({filteredProducts.length} productos)
            </h2>
            <div className="relative w-full sm:w-80">
              <FaSearch className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
              <input
                type="text"
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                placeholder="Buscar por código o nombre..."
                className="w-full pl-10 pr-4 py-2 text-sm border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#219ebc]"
              />
            </div>
          </div>

          <div className="overflow-x-auto border border-slate-200 rounded-xl">
            <table className="w-full text-left border-collapse">
              <thead>
                <tr className="bg-[#023047] text-white text-xs font-bold uppercase">
                  <th className="py-3 px-4 w-20">Código</th>
                  <th className="py-3 px-4">Nombre del Producto</th>
                  <th className="py-3 px-4 text-right">Precio Unitario</th>
                  <th className="py-3 px-4 text-center">Existencias (Stock)</th>
                  <th className="py-3 px-4 text-center w-52">Ajuste Rápido</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-200 text-sm">
                {filteredProducts.length === 0 ? (
                  <tr>
                    <td colSpan="5" className="py-8 text-center text-slate-400">
                      No hay productos que coincidan con la búsqueda.
                    </td>
                  </tr>
                ) : (
                  filteredProducts.map((product) => {
                    const currentStock = Number(product.stock) || 0;
                    return (
                      <tr key={product.id} className="hover:bg-slate-50 transition">
                        <td className="py-3 px-4 font-mono text-xs text-slate-500">
                          #{product.id}
                        </td>
                        <td className="py-3 px-4 font-semibold text-slate-800">
                          {product.name}
                        </td>
                        <td className="py-3 px-4 text-right font-bold text-[#023047]">
                          $ {formatPrice(product.price)}
                        </td>
                        <td className="py-3 px-4 text-center">
                          <span
                            className={`inline-block px-3 py-1 rounded-full text-xs font-extrabold ${
                              currentStock > 5
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
                          <div className="flex items-center justify-center gap-2">
                            <button
                              type="button"
                              onClick={() => adjustStock(product, 1)}
                              className="verde px-3 py-1.5 rounded-lg text-xs font-bold inline-flex items-center gap-1 shadow-2xs"
                            >
                              <FaPlus /> Entrada
                            </button>
                            <button
                              type="button"
                              disabled={currentStock <= 0}
                              onClick={() => adjustStock(product, -1)}
                              className="rojo px-3 py-1.5 rounded-lg text-xs font-bold inline-flex items-center gap-1 shadow-2xs disabled:opacity-40"
                            >
                              <FaMinus /> Salida
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
      </div>
    </div>
  );
};

export default Inventory;
