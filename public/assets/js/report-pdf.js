function exportPdf() {
  if (typeof pdfMake === 'undefined') {
    alert('PDF library has not loaded. Please refresh the page and try again.');
    return;
  }

  const button = document.querySelector('[onclick="exportPdf()"]');

  // Prevent double-clicks
  if (button) {
    button.disabled = true;
    button.style.opacity = '0.6';
    button.style.cursor = 'wait';
  }

  try {
    // ------------------------------------------------------------
    // BUILD TABLE HEADER
    // ------------------------------------------------------------
    const tableHeader = [
      {
        text: 'Date',
        style: 'tableHeader',
        alignment: 'left'
      }
    ];

    ingredients.forEach(function (ingredient) {
      tableHeader.push({
        text: ingredient,
        style: 'tableHeader',
        alignment: 'right'
      });
    });

    // ------------------------------------------------------------
    // BUILD TABLE BODY
    // ------------------------------------------------------------
    const tableBody = [];

    // Header row
    tableBody.push(tableHeader);

    // Data rows
    const saleDates = Object.keys(salesData);

      if (saleDates.length === 0) {

        const emptyRow = [
          {
            text: 'No items sold separately in this date range.',
            colSpan: ingredients.length + 1,
            alignment: 'center',
            color: '#64748b',
            margin: [0, 10, 0, 10]
          }
        ];

        // Fill the remaining cells required by pdfmake
        for (let i = 1; i < ingredients.length + 1; i++) {
          emptyRow.push({});
        }

        tableBody.push(emptyRow);

      } else {

        saleDates.forEach(function (saleDate) {

          const row = [
            {
              text: formatPdfDate(saleDate),
              style: 'tableCell',
              alignment: 'left'
            }
          ];

          ingredients.forEach(function (ingredient) {

            const quantity =
              salesData[saleDate][ingredient] !== undefined
                ? Number(salesData[saleDate][ingredient])
                : 0;

            row.push({
              text: quantity.toFixed(2),
              style: 'tableCell',
              alignment: 'right'
            });

          });

        tableBody.push(row);
      });
    }
    // ------------------------------------------------------------
    // GRAND TOTAL ROW
    // ------------------------------------------------------------

    const totalRow = [
        {
            text: 'GRAND TOTAL\n' + Number(grandTotal).toFixed(2),
            style: 'totalCell',
            alignment: 'left'
        }
    ];

    ingredients.forEach(function (ingredient) {

        const total =
            ingredientTotals[ingredient] !== undefined
                ? Number(ingredientTotals[ingredient])
                : 0;

        totalRow.push({
            text: total.toFixed(2),
            style: 'totalCell',
            alignment: 'right'
        });
    });

    tableBody.push(totalRow);


        // ------------------------------------------------------------
        // COLUMN WIDTHS
        // ------------------------------------------------------------

        const columnWidths = ['auto'];

        ingredients.forEach(function () {
            columnWidths.push('*');
        });


        // ------------------------------------------------------------
        // DOCUMENT DEFINITION
        // ------------------------------------------------------------

        const docDefinition = {

            pageSize: 'A4',

            pageOrientation: 'portrait',

            pageMargins: [30, 55, 30, 45],


            // --------------------------------------------------------
            // FOOTER / PAGE NUMBERS
            // --------------------------------------------------------

            footer: function (currentPage, pageCount) {

                return {
                    columns: [
                        {
                            text: `Generated: ${formatGeneratedDate()}`,
                            alignment: 'left',
                            fontSize: 8,
                            color: '#64748b'
                        },
                        {
                            text: `Page ${currentPage} of ${pageCount}`,
                            alignment: 'right',
                            fontSize: 8,
                            color: '#64748b'
                        }
                    ],
                    margin: [30, 10, 30, 0]
                };

            },


            // --------------------------------------------------------
            // CONTENT
            // --------------------------------------------------------

            content: [

                {
                    text: 'Items Sold Separately Report',
                    style: 'title'
                },

                {
                    text: `From ${startDate} to ${endDate}`,
                    style: 'subtitle'
                },

                {
                    text: '',
                    margin: [0, 5]
                },

                {
                    table: {
                        headerRows: 1,
                        widths: columnWidths,
                        body: tableBody,

                        dontBreakRows: true
                    },

                    layout: {

                        fillColor: function (rowIndex) {

                            if (rowIndex === 0) {
                                return '#f1f5f9';
                            }

                            if (rowIndex === tableBody.length - 1) {
                                return '#f8fafc';
                            }

                            return null;
                        },

                        hLineColor: function () {
                            return '#cbd5e1';
                        },

                        vLineColor: function () {
                            return '#e2e8f0';
                        },

                        hLineWidth: function (i, node) {

                            if (i === 0 || i === node.table.body.length) {
                                return 1;
                            }

                            return 0.5;
                        },

                        vLineWidth: function () {
                            return 0.5;
                        },

                        paddingLeft: function () {
                            return 6;
                        },

                        paddingRight: function () {
                            return 6;
                        },

                        paddingTop: function () {
                            return 5;
                        },

                        paddingBottom: function () {
                            return 5;
                        }
                    }
                }

            ],


        // --------------------------------------------------------
        // STYLES
        // --------------------------------------------------------

        styles: {

            title: {
                fontSize: 18,
                bold: true,
                color: '#0f172a',
                alignment: 'center',
                margin: [0, 0, 0, 6]
            },

            subtitle: {
                fontSize: 9,
                color: '#475569',
                alignment: 'center',
                margin: [0, 0, 0, 4]
            },

            generated: {
                fontSize: 8,
                color: '#94a3b8',
                alignment: 'center',
                margin: [0, 0, 0, 12]
            },

            tableHeader: {
                fontSize: 7,
                bold: true,
                color: '#334155'
            },

            tableCell: {
                fontSize: 8,
                color: '#1e293b'
            },

            totalCell: {
                fontSize: 8,
                bold: true,
                color: '#0f172a'
            }
        },

        defaultStyle: {
            fontSize: 8
        }
    };


  // ------------------------------------------------------------
  // CREATE PDF
  // ------------------------------------------------------------

  pdfMake
    .createPdf(docDefinition)
    .open();

  } catch (error) {

    console.error('PDF generation failed:', error);

    alert(
      'There was a problem generating the PDF. ' +
      'Please check the browser console for details.'
    );

  } finally {

    if (button) {
      button.disabled = false;
      button.style.opacity = '';
      button.style.cursor = '';
    }
  }
}


function formatPdfDate(dateString) {
  const date = new Date(dateString + 'T00:00:00');

  if (Number.isNaN(date.getTime())) {
    return dateString;
  }

  const day = String(date.getDate()).padStart(2, '0');

  const months = [
    'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
    'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
  ];

  return `${day}-${months[date.getMonth()]}-${date.getFullYear()}`;
}


function formatGeneratedDate() {
  const now = new Date();

  return now.toLocaleString('en-US', {
    month: 'long',
    day: 'numeric',
    year: 'numeric',
    hour: 'numeric',
    minute: '2-digit'
  });
}
