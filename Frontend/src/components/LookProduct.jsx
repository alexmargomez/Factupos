import React, { useState, useEffect } from 'react';
import { FaCartPlus } from 'react-icons/fa';

const LookProduct = ({ addProduct, searchTerm }) => {
  const API_URL = import.meta.env.VITE_API_URL;
  const [products, setProducts] = useState([]);
  const [units, setUnits] = useState({});
  const [loading, setLoading] = useState(true);
  const token = localStorage.getItem('authToken');

  useEffect(() => {
    const fetchProducts = async () => {
      setLoading(true);
      try {
        const response = await fetch(`${API_URL}/products`, {
          headers: {
            'Authorization': `Bearer ${token}`,
          },
        });
        if (response.ok) {
          const result = await response.json();
          setProducts(Array.isArray(result) ? result : []);
        }
      } catch (error) {
        console.error('Error fetching products:', error);
      } finally {
        setLoading(false);
      }
    };

    fetchProducts();
  }, [API_URL, token]);

  const filterProducts = products.filter((product) => {
    const term = (searchTerm || '').toLowerCase().trim();
    if (!term) return true;
    return (
      product.name?.toLowerCase().includes(term) ||
      String(product.id).includes(term)
    );
  });

  const handleUnitChange = (productId, rawValue) => {
    if (rawValue === '') {
      setUnits((prev) => ({ ...prev, [productId]: '' }));
      return;
    }
    const parsed = parseInt(rawValue, 10);
    if (!isNaN(parsed) && parsed >= 1) {
      setUnits((prev) => ({ ...prev, [productId]: parsed }));
    }
  };

  const formatPrice = (price) => {
    return (Number(price) || 0).toLocaleString('es-CO');
  };

  if (loading) {
    return (
      <div className="py-8 text-center text-slate-400 text-sm">
        Cargando catálogo de productos...
      </div>
    );
  }

  if (products.length === 0) {
    return (
      <div className="py-8 text-center text-slate-500 text-sm">
        No hay productos registrados en el inventario.
      </div>
    );
  }

  return (
    <div className="w-full overflow-x-auto">
      <table className="w-full text-left border-collapse">
        <thead>
          <tr className="border-b border-slate-200 text-xs font-bold uppercase text-slate-500 bg-slate-50">
            <th className="py-2.5 px-2 w-14">Cód.</th>
            <th className="py-2.5 px-2">Producto</th>
            <th className="py-2.5 px-2 text-right">Precio</th>
            <th className="py-2.5 px-2 text-center">Stock</th>
            <th className="py-2.5 px-2 text-center w-20">Cant.</th>
            <th className="py-2.5 px-2 text-center w-24">Acción</th>
          </tr>
        </thead>
        <tbody className="divide-y divide-slate-100 text-sm">
          {filterProducts.length === 0 ? (
            <tr>
              <td colSpan="6" className="py-6 text-center text-slate-400">
                No se encontraron productos para &ldquo;{searchTerm}&rdquo;
              </td>
            </tr>
          ) : (
            filterProducts.map((product) => {
              const stockNum = Number(product.stock) || 0;
              const currentQty = units[product.id] === undefined ? 1 : units[product.id];
              return (
                <tr key={product.id} className="hover:bg-slate-50/80 transition">
                  <td className="py-2.5 px-2 font-mono text-xs text-slate-500">
                    #{product.id}
                  </td>
                  <td className="py-2.5 px-2 font-semibold text-slate-800">
                    {product.name}
                  </td>
                  <td className="py-2.5 px-2 text-right font-bold text-[#023047] whitespace-nowrap">
                    $ {formatPrice(product.price)}
                  </td>
                  <td className="py-2.5 px-2 text-center">
                    <span
                      className={`inline-block px-2 py-0.5 rounded-full text-xs font-bold ${
                        stockNum > 5
                          ? 'bg-emerald-100 text-emerald-800'
                          : stockNum > 0
                          ? 'bg-amber-100 text-amber-800'
                          : 'bg-rose-100 text-rose-700'
                      }`}
                    >
                      {stockNum}
                    </span>
                  </td>
                  <td className="py-2.5 px-2 text-center">
                    <input
                      type="number"
                      min="1"
                      value={currentQty}
                      onChange={(e) => handleUnitChange(product.id, e.target.value)}
                      className="w-16 px-2 py-1 text-center border border-slate-300 rounded-lg text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-[#219ebc]"
                    />
                  </td>
                  <td className="py-2.5 px-2 text-center">
                    <button
                      type="button"
                      onClick={() => {
                        const validQty = Math.max(1, parseInt(currentQty, 10) || 1);
                        addProduct({ ...product, und: validQty });
                        setUnits((prev) => ({ ...prev, [product.id]: 1 }));
                      }}
                      className="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg bg-[#023047] hover:bg-[#fb8500] text-white text-xs font-bold transition shadow-2xs"
                    >
                      <FaCartPlus />
                      <span>Agregar</span>
                    </button>
                  </td>
                </tr>
              );
            })
          )}
        </tbody>
      </table>
    </div>
  );
};

export default LookProduct;