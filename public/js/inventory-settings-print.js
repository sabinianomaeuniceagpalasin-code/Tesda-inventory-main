document.addEventListener("DOMContentLoaded", function () {

    const modal = new bootstrap.Modal(document.getElementById('printPreviewModal'));

    document.querySelectorAll('.openPrintModal').forEach(button => {
        button.addEventListener('click', function () {

            let itemName = this.dataset.item;
            let serialString = this.dataset.serials;
            let type = this.dataset.type; // qr or barcode

            let serials = serialString.split(',').map(s => s.trim());

            let container = document.getElementById('qrContainer');
            container.innerHTML = '';

            // 🔥 store type so printPreview() can detect it
            container.setAttribute("data-type", type);

            serials.forEach(serial => {

                let box = document.createElement('div');
                box.className = 'qr-box';

                let codeDiv = document.createElement('div');
                box.appendChild(codeDiv);

                if (type === 'qr') {

                    new QRCode(codeDiv, {
                        text: serial,
                        width: 70,
                        height: 70
                    });

                } else if (type === 'barcode') {

                    let img = document.createElement('img');

                    // remove quiet zone
                    img.src = `https://barcode.tec-it.com/barcode.ashx?data=${encodeURIComponent(serial)}&code=Code128&quiet=0`;

                    img.style.width = "120px";
                    img.style.height = "40px";

                    codeDiv.appendChild(img);
                }

                let label = document.createElement('div');
                label.className = 'qr-title';
                label.innerHTML = "<strong>" + itemName + "</strong><br>" + serial;

                box.appendChild(label);
                container.appendChild(box);
            });

            modal.show();
        });
    });

});

function printPreview() {

    let container = document.getElementById("qrContainer");
    let type = container.getAttribute("data-type");
    let printContents = container.innerHTML;

    let printWindow = window.open('', '', 'width=900,height=1200');

    // 🔥 Dynamic grid layout
    let gridColumns;
    let boxWidth;

    if (type === 'barcode') {
        gridColumns = "repeat(5, 130px)";  // 5 per row
        boxWidth = "130px";
    } else {
        gridColumns = "repeat(7, 90px)";   // 7 per row
        boxWidth = "90px";
    }

    printWindow.document.write(`
        <html>
        <head>
            <title>Print Codes</title>
            <style>
                body {
                    margin: 0;
                    padding: 20px;
                    font-family: Arial, sans-serif;
                }

                .qr-container {
                    display: grid;
                    grid-template-columns: ${gridColumns};
                    justify-content: center;
                    gap: 8px;
                }

                .qr-box {
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    text-align: center;
                    width: ${boxWidth};
                }

                .qr-box canvas {
                    width: 70px !important;
                    height: 70px !important;
                }

                .qr-box img {
                    display: block;
                    margin: 0 auto;
                }

                .qr-title {
                    font-size: 9px;
                    margin-top: 4px;
                    line-height: 1.2;
                    text-align: center;
                }
            </style>
        </head>
        <body>
            <div class="qr-container">
                ${printContents}
            </div>
        </body>
        </html>
    `);

    printWindow.document.close();
    printWindow.focus();

    setTimeout(() => {
        printWindow.print();
        printWindow.close();
    }, 500);
}