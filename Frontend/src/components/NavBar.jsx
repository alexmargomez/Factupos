import React, { useState } from "react";
import { useNavigate, useLocation } from "react-router-dom";
import { AiFillProduct } from "react-icons/ai";
import { FaBookBookmark } from "react-icons/fa6";
import { HiDocumentReport } from "react-icons/hi";
import { HiOutlineDocumentMagnifyingGlass } from "react-icons/hi2";
import { FaDoorClosed, FaDoorOpen } from "react-icons/fa";
import { Logout } from "./LoginVal";
import { MdDashboard, MdOutlinePointOfSale } from "react-icons/md";

const NavBar = () => {
  const [isOpen, setIsOpen] = useState(false);
  const navigate = useNavigate();
  const location = useLocation();

  const navItems = [
    { icon: MdDashboard, label: "Inicio", route: "/dashboard" },
    { icon: MdOutlinePointOfSale, label: "Facturar", route: "/sales" },
    { icon: FaBookBookmark, label: "Agendar", route: "/schedule" },
    { icon: AiFillProduct, label: "Inventario", route: "/inventory" },
    { icon: HiOutlineDocumentMagnifyingGlass, label: "Consultas", route: "/look" },
    { icon: HiDocumentReport, label: "Reportes", route: "/reports" },
  ];

  const handleLogout = Logout(navigate);

  return (
    <aside className="bg-[#023047] text-white w-full md:w-20 lg:w-52 md:min-h-screen flex md:flex-col justify-between items-center py-2 px-3 md:py-5 md:px-3 shadow-xl shrink-0 z-30 border-b md:border-b-0 md:border-r border-slate-700/50">
      {/* Brand / Header */}
      <div
        onClick={() => navigate("/dashboard")}
        className="hidden md:flex items-center gap-3 w-full px-2 pb-5 mb-2 border-b border-white/10 cursor-pointer group"
        title="Ir al Panel Principal"
      >
        <div className="w-10 h-10 rounded-xl bg-[#ffb703] text-[#023047] flex items-center justify-center font-black text-lg shadow-md shrink-0 group-hover:scale-105 transition-transform">
          EM
        </div>
        <div className="hidden lg:flex flex-col overflow-hidden">
          <span className="font-extrabold text-sm tracking-wide text-white truncate">
            ELECTRIMOTOS
          </span>
          <span className="text-[11px] text-sky-300 font-medium truncate">
            FactuPOS Sistema
          </span>
        </div>
      </div>

      {/* Navigation Links */}
      <nav className="flex md:flex-col items-center justify-around md:justify-start w-full gap-1 md:gap-2 flex-1">
        {navItems.map((item) => {
          const IconComponent = item.icon;
          const isActive = location.pathname === item.route;
          return (
            <button
              key={item.route}
              type="button"
              onClick={() => navigate(item.route)}
              title={item.label}
              className={`flex flex-col lg:flex-row items-center justify-center lg:justify-start gap-1 lg:gap-3 w-full px-2 py-2.5 lg:px-3.5 lg:py-3 rounded-xl transition-all duration-150 ${
                isActive
                  ? "bg-[#ffb703] text-[#023047] font-bold shadow-md"
                  : "text-slate-200 hover:bg-white/10 hover:text-white font-medium"
              }`}
            >
              <IconComponent className="text-2xl shrink-0" />
              <span className="text-[10px] md:text-[11px] lg:text-sm truncate">
                {item.label}
              </span>
            </button>
          );
        })}
      </nav>

      {/* Logout Button */}
      <button
        type="button"
        onMouseEnter={() => setIsOpen(true)}
        onMouseLeave={() => setIsOpen(false)}
        onClick={handleLogout}
        title="Cerrar sesión"
        className="flex flex-col lg:flex-row items-center justify-center lg:justify-start gap-1 lg:gap-3 md:w-full px-3 py-2.5 lg:px-3.5 lg:py-3 rounded-xl text-rose-300 hover:bg-rose-500/20 hover:text-rose-200 transition-all duration-150 md:mt-4 md:border-t md:border-white/10"
      >
        {isOpen ? (
          <FaDoorOpen className="text-2xl shrink-0" />
        ) : (
          <FaDoorClosed className="text-2xl shrink-0" />
        )}
        <span className="hidden md:inline text-[11px] lg:text-sm font-medium">
          Salir
        </span>
      </button>
    </aside>
  );
};

export default NavBar;