import { FaFileInvoiceDollar } from "react-icons/fa";
import { MdOutlineInventory, MdOutlinePointOfSale, MdShoppingCartCheckout } from "react-icons/md";
import { FaUsersViewfinder, FaBookBookmark } from "react-icons/fa6";
import { TbReportAnalytics } from "react-icons/tb";
import { PiMotorcycleFill } from "react-icons/pi";
import DateTimeDisplay from "../components/DateTimeDisplay";
import { useNavigate } from "react-router-dom";

const Dashboard = () => {
  const navigate = useNavigate();

  const blocks = [
    {
      gradient: "from-[#023047] to-[#055075]",
      number: "3",
      name: "Ventas y Facturas",
      desc: "Consultar historial de ventas e imprimir facturas",
      icon: <FaFileInvoiceDollar />,
    },
    {
      gradient: "from-[#219ebc] to-[#126782]",
      number: "0",
      name: "Productos y Stock",
      desc: "Catálogo de repuestos, precios y existencias",
      icon: <MdOutlineInventory />,
    },
    {
      gradient: "from-[#023047] to-[#055075]",
      number: "1",
      name: "Directorio Clientes",
      desc: "Clientes registrados e historial de servicios",
      icon: <FaUsersViewfinder />,
    },
    {
      gradient: "from-[#219ebc] to-[#126782]",
      number: "2",
      name: "Vehículos",
      desc: "Motos registradas por placa, marca y modelo",
      icon: <PiMotorcycleFill />,
    },
    {
      gradient: "from-[#023047] to-[#055075]",
      number: "4",
      name: "Citas Pendientes",
      desc: "Órdenes de servicio en taller por facturar",
      icon: <MdShoppingCartCheckout />,
    },
    {
      gradient: "from-[#219ebc] to-[#126782]",
      number: "5",
      name: "Movimientos",
      desc: "Entradas y salidas de inventario detalladas",
      icon: <TbReportAnalytics />,
    },
  ];

  const handleOptionClick = (number) => {
    localStorage.setItem("number", number);
    navigate(`/look`);
  };

  return (
    <div className="min-h-screen w-full flex flex-col bg-slate-100 p-4 md:p-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-4">
        <div>
          <h1 className="text-2xl font-extrabold text-[#023047] tracking-tight">
            FACTUPOS — PANEL PRINCIPAL
          </h1>
          <p className="text-sm text-slate-500">
            Panel de control principal y accesos rápidos
          </p>
        </div>
        <DateTimeDisplay />
      </div>

      {/* Accesos rápidos principales */}
      <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-5">
        <button
          type="button"
          onClick={() => navigate("/sales")}
          className="flex items-center justify-between p-5 rounded-2xl bg-gradient-to-r from-[#ffb703] to-[#fb8500] text-[#023047] shadow-md hover:shadow-lg transition-all group text-left"
        >
          <div>
            <span className="text-xs font-bold uppercase tracking-wider bg-white/40 px-2.5 py-0.5 rounded-full">
              Punto de Venta (POS)
            </span>
            <h2 className="text-2xl font-black mt-1">Nueva Factura / Venta</h2>
            <p className="text-sm font-medium text-[#023047]/80">
              Facturar productos, repuestos y mano de obra al instante
            </p>
          </div>
          <MdOutlinePointOfSale className="text-5xl opacity-90 group-hover:scale-110 transition-transform shrink-0" />
        </button>

        <button
          type="button"
          onClick={() => navigate("/schedule")}
          className="flex items-center justify-between p-5 rounded-2xl bg-white border-2 border-[#023047] text-[#023047] shadow-sm hover:bg-slate-50 transition-all group text-left"
        >
          <div>
            <span className="text-xs font-bold uppercase tracking-wider bg-sky-100 text-sky-800 px-2.5 py-0.5 rounded-full">
              Taller y Mantenimiento
            </span>
            <h2 className="text-2xl font-black mt-1">Agendar Servicio</h2>
            <p className="text-sm text-slate-600">
              Registrar ingreso de vehículo y asignar técnico encargado
            </p>
          </div>
          <FaBookBookmark className="text-4xl text-[#219ebc] group-hover:scale-110 transition-transform shrink-0" />
        </button>
      </div>

      {/* Grilla de módulos de consulta */}
      <div className="flex-1 bg-white rounded-2xl shadow-sm border border-slate-200 border-t-4 border-t-[#ffb703] p-5">
        <h3 className="text-sm font-bold uppercase tracking-wider text-slate-400 mb-4">
          Módulos de Consulta y Gestión
        </h3>
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          {blocks.map((block, index) => (
            <div
              key={index}
              onClick={() => handleOptionClick(block.number)}
              className={`bg-gradient-to-br ${block.gradient} hover:from-[#fb8500] hover:to-[#ffb703] rounded-2xl p-6 text-white shadow-md hover:shadow-xl transition-all duration-200 cursor-pointer flex items-center justify-between gap-4 group min-h-[150px]`}
            >
              <div className="flex flex-col justify-center">
                <h4 className="text-2xl font-extrabold tracking-tight group-hover:text-[#023047] transition-colors">
                  {block.name}
                </h4>
                <p className="text-xs text-white/80 mt-1.5 group-hover:text-[#023047]/90 font-medium">
                  {block.desc}
                </p>
              </div>
              <div className="text-6xl text-white/90 group-hover:text-[#023047] group-hover:scale-110 transition-all shrink-0">
                {block.icon}
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
};

export default Dashboard;
