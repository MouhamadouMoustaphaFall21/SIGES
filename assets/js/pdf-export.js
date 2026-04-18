/**
 * Utilitaire pour exporter des éléments HTML en PDF
 * Utilise html2pdf pour la conversion
 */

// Charger la bibliothèque html2pdf si elle n'existe pas
if (typeof html2pdf === 'undefined') {
    const script = document.createElement('script');
    script.src = 'https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js';
    document.head.appendChild(script);
}

/**
 * Exporte un élément HTML en PDF
 * @param {string} elementId - ID de l'élément à exporter
 * @param {string} filename - Nom du fichier PDF
 */
function downloadPDFFromElement(elementId, filename) {
    const element = document.getElementById(elementId);
    
    if (!element) {
        console.error(`Élément avec l'ID "${elementId}" non trouvé`);
        return;
    }

    // Attendre que html2pdf soit chargé
    const checkLibrary = setInterval(() => {
        if (typeof html2pdf !== 'undefined') {
            clearInterval(checkLibrary);
            
            const options = {
                margin: 10,
                filename: filename,
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2 },
                jsPDF: { orientation: 'portrait', unit: 'mm', format: 'a4' }
            };
            
            // Cloner l'élément pour éviter de modifier le DOM original
            const clone = element.cloneNode(true);
            
            // Masquer les éléments non nécessaires pour l'export
            const buttonsToHide = clone.querySelectorAll('.button-primary, .button-secondary, .button-danger, .button-success, .button-warning, .button-small, a.button-*');
            buttonsToHide.forEach(btn => btn.style.display = 'none');
            
            html2pdf().set(options).from(clone).save();
        }
    }, 100);
}

/**
 * Exporte l'emploi du temps actuel en PDF
 * @param {string} filename - Nom du fichier (ex: "emploi-du-temps.pdf")
 */
function downloadSchedulePDF(filename = 'emploi-du-temps.pdf') {
    const scheduleElement = document.querySelector('.schedule-table, table');
    
    if (!scheduleElement) {
        alert('Impossible de trouver le tableau d\'emploi du temps');
        return;
    }

    // Attendre que html2pdf soit chargé
    const checkLibrary = setInterval(() => {
        if (typeof html2pdf !== 'undefined') {
            clearInterval(checkLibrary);
            
            const options = {
                margin: 10,
                filename: filename,
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2 },
                jsPDF: { orientation: 'landscape', unit: 'mm', format: 'a4' }
            };
            
            const clone = scheduleElement.cloneNode(true);
            
            // Ajouter un titre si la page n'en a pas
            const container = document.createElement('div');
            const pageHeader = document.querySelector('.page-header h1');
            if (pageHeader) {
                const title = document.createElement('h2');
                title.textContent = pageHeader.textContent;
                title.style.marginBottom = '20px';
                container.appendChild(title);
            }
            
            container.appendChild(clone);
            
            html2pdf().set(options).from(container).save();
        }
    }, 100);
}

/**
 * Exporte les notes/PV en PDF
 * @param {string} filename - Nom du fichier
 */
function downloadNotesPDF(filename = 'notes.pdf') {
    const notesElement = document.querySelector('.grades-table, .notes-section, table');
    
    if (!notesElement) {
        alert('Impossible de trouver les notes');
        return;
    }

    // Attendre que html2pdf soit chargé
    const checkLibrary = setInterval(() => {
        if (typeof html2pdf !== 'undefined') {
            clearInterval(checkLibrary);
            
            const options = {
                margin: 10,
                filename: filename,
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2 },
                jsPDF: { orientation: 'portrait', unit: 'mm', format: 'a4' }
            };
            
            const clone = notesElement.cloneNode(true);
            
            // Ajouter un titre
            const container = document.createElement('div');
            const pageHeader = document.querySelector('.page-header h1');
            if (pageHeader) {
                const title = document.createElement('h2');
                title.textContent = pageHeader.textContent;
                title.style.marginBottom = '20px';
                container.appendChild(title);
            }
            
            container.appendChild(clone);
            
            html2pdf().set(options).from(container).save();
        }
    }, 100);
}

/**
 * Utilise la méthode print() du navigateur pour télécharger en PDF
 * (Approche fallback si html2pdf n'est pas disponible)
 */
function downloadViaSystemPrint() {
    window.print();
}
