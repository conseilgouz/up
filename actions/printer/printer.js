function pdf_print(printArea, filename = '') {
    var printContents = document.querySelector(printArea).innerHTML;
    var originalContents = document.body.innerHTML;
    var originalTitle = document.title;
    const iframe = document.createElement('iframe');
    iframe.style.display = 'none';
    document.body.appendChild(iframe);

    const doc = iframe.contentWindow.document;
    const styles = Array.from(document.querySelectorAll('style, link[rel="stylesheet"]'))
    .map(el => el.outerHTML).join('');

    doc.open();
    doc.write(`
        <html>
            <head>
                <title>${filename}</title>
                ${styles}
            </head>
            <body>
                ${printContents}
            </body>
        </html>
    `);
    doc.close();

    iframe.onload = function () {
        iframe.contentWindow.focus();
        iframe.contentWindow.print();
        setTimeout(() => document.body.removeChild(iframe), 1000);
    };
}