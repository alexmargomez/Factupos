import React, { useState, useEffect } from 'react';

const DateTimeDisplay = ({ className = "" }) => {
  const [dateTime, setDateTime] = useState(() => new Date().toLocaleString("es-CO"));

  useEffect(() => {
    const intervalId = setInterval(() => {
      setDateTime(new Date().toLocaleString("es-CO"));
    }, 1000);

    return () => clearInterval(intervalId);
  }, []);

  return (
    <div className={`inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white border border-slate-200 shadow-2xs text-xs sm:text-sm font-semibold text-slate-600 ${className}`}>
      <span className="w-2 h-2 rounded-full bg-emerald-500 animate-pulse" />
      <span>{dateTime}</span>
    </div>
  );
};

export default DateTimeDisplay;
