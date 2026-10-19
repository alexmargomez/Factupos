import { useEffect } from 'react';

const FactuPrint = ({ id }) => {
  const token = localStorage.getItem('authToken');
  const API_URL = import.meta.env.VITE_API_URL;

  useEffect(() => {
    if (!id) return;

    const fetchInvoices = async () => {
      try {
        const response = await fetch(`${API_URL}/invoices/pdf/${id}`, {
          headers: {
            'Authorization': `Bearer ${token}`,
          },
        });

        if (!response.ok) {
          throw new Error('No se pudo generar el documento de la factura');
        }

        const contentType = response.headers.get('content-type') || '';
        const printWindow = window.open('', '_blank', 'width=900,height=700');

        if (!printWindow) {
          console.error('El navegador bloqueó la ventana emergente de impresión.');
          return;
        }

        if (contentType.includes('text/html')) {
          const htmlText = await response.text();
          printWindow.document.open();
          printWindow.document.write(htmlText);
          printWindow.document.close();
          printWindow.focus();
          setTimeout(() => {
            printWindow.print();
          }, 400);
        } else {
          const blob = await response.blob();
          const url = window.URL.createObjectURL(blob);

          printWindow.document.open();
          printWindow.document.write(`
            <!DOCTYPE html>
            <html>
              <head><title>Factura #${id}</title></head>
              <body style="margin:0;padding:0;overflow:hidden;">
                <iframe id="pdf-frame" src="${url}" frameborder="0" style="border:0;width:100vw;height:100vh;" allowfullscreen></iframe>
              </body>
            </html>
          `);
          printWindow.document.close();

          const iframe = printWindow.document.getElementById('pdf-frame');
          if (iframe) {
            iframe.onload = () => {
              setTimeout(() => {
                try {
                  printWindow.focus();
                  printWindow.print();
                } catch (e) {
                  console.error('Error al invocar impresión:', e);
                }
              }, 500);
            };
          }
        }
      } catch (error) {
        console.error('Error fetching invoice:', error);
      }
    };

    fetchInvoices();
  }, [id, token, API_URL]);

  return null;
};

export default FactuPrint;
