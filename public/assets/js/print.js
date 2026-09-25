async function exportReportPdf(button) {
  const targetId = button?.dataset.pdfTarget || 'printable-report';
  const report = document.getElementById(targetId);

  if (!report || typeof html2pdf === 'undefined') {
    window.print();
    return;
  }

  const filename = button.dataset.pdfFilename || 'report.pdf';
  const orientation = button.dataset.pdfOrientation || 'landscape';
  const worker = html2pdf().set({
    margin: [10, 10, 14, 10],
    filename,
    image: { type: 'jpeg', quality: 0.98 },
    html2canvas: { scale: 2, useCORS: true, backgroundColor: '#ffffff' },
    jsPDF: { unit: 'mm', format: 'a4', orientation },
    pagebreak: { mode: ['css', 'legacy'], avoid: ['tr', 'thead', 'tfoot'] }
  }).from(report).toPdf();

  const pdf = await worker.get('pdf');
  const pageCount = pdf.internal.getNumberOfPages();
  const pageWidth = pdf.internal.pageSize.getWidth();
  const pageHeight = pdf.internal.pageSize.getHeight();

  for (let page = 1; page <= pageCount; page += 1) {
    pdf.setPage(page);
    pdf.setFontSize(8);
    pdf.setTextColor(100, 116, 139);
    pdf.text(`Page ${page} of ${pageCount}`, pageWidth - 10, pageHeight - 6, { align: 'right' });
  }

  const pdfUrl = await worker.outputPdf('bloburl');
  window.open(pdfUrl, '_blank', 'noopener');
}

function logReportPrint(reportName, button) {
  const startDate = document.querySelector('input[name="start_date"]')?.value || '';
  const endDate = document.querySelector('input[name="end_date"]')?.value || '';
  const endpoint = button?.dataset.printLogEndpoint;

  if (!endpoint || !reportName) {
    window.print();
    return;
  }

  const data = new URLSearchParams({ report_name: reportName });
  if (startDate) data.set('start_date', startDate);
  if (endDate) data.set('end_date', endDate);

  if (navigator.sendBeacon) {
    navigator.sendBeacon(endpoint, new Blob([data.toString()], { type: 'application/x-www-form-urlencoded' }));
  } else {
    fetch(endpoint, {
      method: 'POST',
      body: data,
      credentials: 'same-origin',
      keepalive: true
    });
  }

  if (button?.dataset.pdfTarget || document.getElementById('printable-report')) {
  //   exportReportPdf(button);
  // } else {
    window.print();
  }
}