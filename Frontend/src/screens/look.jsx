import { useState, useEffect } from "react";
import LookDetail from "../components/LookDetail";
import DateTimeDisplay from "../components/DateTimeDisplay";
import { FaSearch, FaTimes } from "react-icons/fa";

const Look = () => {
  const options = [
    "Productos",
    "Clientes",
    "Vehiculos",
    "Ventas",
    "Pendientes",
    "Movimientos",
  ];

  const [number, setNumber] = useState(() => {
    const savedNumber = parseInt(localStorage.getItem("number") || "0", 10);
    return !isNaN(savedNumber) && savedNumber >= 0 && savedNumber < options.length
      ? savedNumber
      : 0;
  });

  const [selectedOption, setSelectedOption] = useState(
    options[number] || "Productos"
  );
  const [searchTerm, setSearchTerm] = useState("");

  useEffect(() => {
    localStorage.setItem("number", String(number));
  }, [number]);

  const handleOptionClick = (option, idx) => {
    setSelectedOption(option);
    setNumber(idx);
  };

  return (
    <div className="flex flex-col min-h-screen w-full bg-slate-100 p-4 md:p-6 gap-4">
      {/* Encabezado */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2 bg-white px-5 py-3.5 rounded-xl shadow-sm border border-slate-200">
        <div>
          <h1 className="text-xl font-extrabold text-[#023047] tracking-tight">
            CONSULTAS Y GESTIÓN DE REGISTROS
          </h1>
          <p className="text-xs text-slate-500">
            Visualiza, edita o filtra productos, clientes, vehículos, facturas, citas y movimientos
          </p>
        </div>
        <DateTimeDisplay />
      </div>

      {/* Pestañas y Buscador */}
      <div className="bg-white p-4 rounded-xl shadow-sm border border-slate-200 flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-4">
        {/* Pestañas de módulos */}
        <div className="flex flex-wrap gap-1.5">
          {options.map((option, idx) => {
            const isActive = selectedOption === option;
            return (
              <button
                key={option}
                type="button"
                onClick={() => handleOptionClick(option, idx)}
                className={`px-3.5 py-2 rounded-lg text-sm font-semibold transition-all cursor-pointer ${
                  isActive
                    ? "bg-[#023047] text-white shadow-sm"
                    : "bg-slate-100 text-slate-600 hover:bg-slate-200 hover:text-slate-900"
                }`}
              >
                {option === "Vehiculos" ? "Vehículos" : option}
              </button>
            );
          })}
        </div>

        {/* Buscador */}
        <div className="relative flex-1 max-w-md">
          <FaSearch className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm" />
          <input
            type="text"
            placeholder={`Buscar en ${selectedOption.toLowerCase()}...`}
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            className="w-full pl-9 pr-9 py-2 rounded-lg border border-slate-300 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#219ebc] focus:border-transparent"
          />
          {searchTerm && (
            <button
              type="button"
              onClick={() => setSearchTerm("")}
              className="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-1 cursor-pointer"
              title="Limpiar búsqueda"
            >
              <FaTimes className="text-xs" />
            </button>
          )}
        </div>
      </div>

      {/* Tabla de resultados */}
      <div className="flex-1 bg-white rounded-xl shadow-sm border border-slate-200 p-4 md:p-5 overflow-x-auto">
        <LookDetail
          selectedOption={selectedOption}
          searchTerm={searchTerm}
          setNumber={setNumber}
        />
      </div>
    </div>
  );
};

export default Look;