(function () {
    // Apply blueprint mode class to body immediately
    document.documentElement.classList.add('blueprint-mode');
    document.body.classList.add('blueprint-mode');

    // Walk the DOM tree recursively
    function walkDOM(node) {
        // Skip specific nodes
        if (node.nodeType === Node.ELEMENT_NODE) {
            const tag = node.tagName.toUpperCase();
            if (tag === 'SCRIPT' || tag === 'STYLE' || tag === 'NOSCRIPT' || tag === 'IFRAME' || tag === 'TEXTAREA' || tag === 'OPTION') {
                return;
            }
        }

        if (node.nodeType === Node.TEXT_NODE) {
            transformTextNode(node);
        } else {
            let child = node.firstChild;
            while (child) {
                const next = child.nextSibling;
                walkDOM(child);
                child = next;
            }
        }
    }

    // Process a text node
    function transformTextNode(node) {
        const val = node.nodeValue;
        const trimmed = val.trim();
        if (!trimmed) return;

        // Skip script, style, iframe tags
        const parent = node.parentElement;
        if (parent) {
            const tagName = parent.tagName.toUpperCase();
            if (tagName === 'SCRIPT' || tagName === 'STYLE' || tagName === 'NOSCRIPT' || tagName === 'IFRAME') {
                return;
            }
            // Skip if already a wireframe bar or inside label/box
            if (parent.classList.contains('wireframe-text-bar') || parent.classList.contains('label') || parent.classList.contains('wireframe-crossed-box')) {
                return;
            }
        }

        const lowercase = trimmed.toLowerCase();

        // If the text contains or relates to charts, represent it as "grafik" directly
        if (lowercase.includes('grafik')) {
            node.nodeValue = val.toLowerCase().replace(/grafik.*/g, 'grafik');
            return;
        }

        // Date / Time keywords or formats
        const dateKeywords = ['januari', 'februari', 'maret', 'april', 'mei', 'juni', 'juli', 'agustus', 'september', 'oktober', 'november', 'desember', 'tahun', 'bulan', 'tanggal', 'periode', 'date', 'time', 'y-m-d'];
        const isDateRelated = dateKeywords.some(keyword => lowercase.includes(keyword)) || 
                              /\b(19|20)\d{2}\b/.test(trimmed) || 
                              /\b\d{1,2}[-\/]\d{1,2}[-\/]\d{2,4}\b/.test(trimmed);
        
        if (isDateRelated) {
            node.nodeValue = 'dd/mm/yy';
            return;
        }

        // Numeric formats (e.g. 85.50, 2026, 100%, Rp. 1.250.000)
        // Consists of digits, punctuation, currency signs, but no alphabetic chars (except 'Rp')
        const hasDigits = /[0-9]/.test(trimmed);
        const hasAlphabet = /[a-zA-Z]/.test(trimmed.replace(/\bRp\b/gi, '')); // exclude 'Rp'

        if (hasDigits && !hasAlphabet) {
            // Numeric data -> convert to literal 'xxxxxxxxxxxxxxxxxxxxxxxxxxx'
            node.nodeValue = 'xxxxxxxxxxxxxxxxxxxxxxxxxxx';
            return;
        }

        // Otherwise it is normal text (varchar/char) -> replace with solid black bar
        const isHeader = parent && ['H1', 'H2', 'H3', 'H4', 'H5', 'H6', 'TH'].includes(parent.tagName.toUpperCase());
        
        const span = document.createElement('span');
        span.className = isHeader ? 'wireframe-text-bar header-bar' : 'wireframe-text-bar body-bar';
        
        // Proportional width based on text length
        const charWidth = isHeader ? 8 : 6;
        const calculatedWidth = Math.min(300, Math.max(30, trimmed.length * charWidth));
        span.style.width = calculatedWidth + 'px';
        
        // Preserve spacing around the element
        const spaceBefore = val.startsWith(' ') || val.startsWith('\n') || val.startsWith('\t');
        const spaceAfter = val.endsWith(' ') || val.endsWith('\n') || val.endsWith('\t');
        
        const container = document.createElement('span');
        if (spaceBefore) container.appendChild(document.createTextNode(' '));
        container.appendChild(span);
        if (spaceAfter) container.appendChild(document.createTextNode(' '));
        
        node.parentNode.replaceChild(container, node);
    }

    // Process inputs, placeholders, select options
    function transformControls() {
        document.querySelectorAll('input, select, textarea').forEach(control => {
            if (control.type === 'hidden') return;

            const name = (control.name || '').toLowerCase();
            const id = (control.id || '').toLowerCase();
            const className = (control.className || '').toLowerCase();
            const type = (control.type || '').toLowerCase();
            
            // Check if specifically year-related
            const isYearRelated = name.includes('tahun') || name.includes('year') ||
                                  id.includes('tahun') || id.includes('year') ||
                                  className.includes('tahun') || className.includes('year') ||
                                  control.closest('.filter-year-container');

            // Check if date/month/period related
            const isDateRelated = type === 'date' || type === 'month' || 
                                  name.includes('date') || name.includes('bulan') || name.includes('periode') ||
                                  id.includes('date') || id.includes('bulan') || id.includes('periode') ||
                                  className.includes('date') || className.includes('bulan') || className.includes('periode') ||
                                  control.closest('.filter-periode-container') || control.closest('.filter-periode-box');

            if (isYearRelated) {
                control.classList.add('wireframe-date-control');
                
                if (control.tagName.toUpperCase() === 'SELECT') {
                    control.querySelectorAll('option').forEach(opt => {
                        opt.textContent = 'yy';
                        opt.classList.add('wireframe-date-option');
                    });
                    if (control.options.length > 0) {
                        control.options[0].textContent = 'yy';
                    }
                } else {
                    if (type === 'date' || type === 'month') {
                        control.type = 'text';
                    }
                    control.value = 'yy';
                }
            } else if (isDateRelated) {
                control.classList.add('wireframe-date-control');
                
                if (control.tagName.toUpperCase() === 'SELECT') {
                    control.querySelectorAll('option').forEach(opt => {
                        opt.textContent = 'dd/mm/yy';
                        opt.classList.add('wireframe-date-option');
                    });
                    if (control.options.length > 0) {
                        control.options[0].textContent = 'dd/mm/yy';
                    }
                } else {
                    if (type === 'date' || type === 'month') {
                        control.type = 'text';
                    }
                    control.value = 'dd/mm/yy';
                }
            }
        });
    }

    // Replace images with wireframe placeholders
    function transformImages() {
        document.querySelectorAll('img').forEach(img => {
            if (img.classList.contains('wireframe-img-placeholder')) return;

            const width = img.getAttribute('width') || img.clientWidth || 80;
            const height = img.getAttribute('height') || img.clientHeight || 80;
            
            const placeholder = document.createElement('div');
            placeholder.className = 'wireframe-crossed-box wireframe-img-placeholder';
            placeholder.style.width = width + 'px';
            placeholder.style.height = height + 'px';
            placeholder.style.display = 'inline-flex';
            
            placeholder.innerHTML = `<div class="label" style="background-color: #ffffff;">FOTO</div>`;
            
            img.parentNode.replaceChild(placeholder, img);
        });
    }

    // Apply schematic layout to logo and chart elements
    function transformDynamicComponents() {
        // Insert Logo in sidebar header
        const sidebarHeader = document.querySelector('.sidebar-header');
        if (sidebarHeader && !sidebarHeader.querySelector('.wireframe-crossed-box')) {
            sidebarHeader.innerHTML = `
                <div class="wireframe-crossed-box" style="width: 100%; height: 60px;">
                    <div class="label" style="background-color: #ffffff;">LOGO</div>
                </div>
            `;
        }

        // Replace actual canvases/charts with grafik box
        document.querySelectorAll('canvas, .chart-container, #chart, #grafik').forEach(chart => {
            if (chart.parentElement.classList.contains('grafik-box-wrapper')) return;
            
            const wrapper = document.createElement('div');
            wrapper.className = 'grafik-box-wrapper';
            wrapper.innerHTML = `
                <div class="wireframe-crossed-box" style="width: 100%; height: 260px; margin: 15px 0;">
                    <div class="label" style="background-color: #ffffff; text-transform: lowercase;">grafik</div>
                </div>
            `;
            chart.parentNode.replaceChild(wrapper, chart);
        });
    }

    // Apply schematic layout to Login Page visual side (Strict B&W Mode)
    function transformLoginPage() {
        const loginVisual = document.querySelector('.login-visual');
        if (loginVisual) {
            loginVisual.innerHTML = `
                <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background-color: #ffffff; display: flex; align-items: center; justify-content: center; padding: 40px; box-sizing: border-box;">
                    <div class="wireframe-crossed-box" style="width: 100%; height: 100%;">
                        <div class="label" style="font-size: 2em; padding: 10px 30px; background-color: #ffffff;">LOGO</div>
                    </div>
                </div>
            `;
        }
    }

    // Main run
    function runTransformation() {
        transformLoginPage();
        transformDynamicComponents();
        transformImages();
        walkDOM(document.body);
        transformControls();
    }

    // Run immediately when script loads
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', runTransformation);
    } else {
        runTransformation();
    }

    // Set up MutationObserver to handle dynamic contents (e.g. AJAX loads, modals, text mutators)
    const observer = new MutationObserver((mutations) => {
        let shouldRun = false;
        for (const mutation of mutations) {
            if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                let hasRealChanges = false;
                mutation.addedNodes.forEach(node => {
                    if (node.nodeType === Node.ELEMENT_NODE) {
                        const tag = node.tagName.toUpperCase();
                        if (tag !== 'SCRIPT' && tag !== 'STYLE' && !node.classList.contains('wireframe-img-placeholder') && !node.classList.contains('wireframe-crossed-box') && !node.classList.contains('wireframe-text-bar')) {
                            hasRealChanges = true;
                        }
                    } else if (node.nodeType === Node.TEXT_NODE) {
                        const val = node.nodeValue.trim();
                        if (val && !val.includes('VARCHAR') && !val.includes('CHAR') && !val.includes('dd/mm/yy') && !val.includes('grafik') && !/^[x.\s,%+\-Rp$]*$/.test(val)) {
                            hasRealChanges = true;
                        }
                    }
                });
                if (hasRealChanges) {
                    shouldRun = true;
                    break;
                }
            } else if (mutation.type === 'characterData') {
                const val = mutation.target.nodeValue.trim();
                if (val && !val.includes('VARCHAR') && !val.includes('CHAR') && !val.includes('dd/mm/yy') && !val.includes('grafik') && !/^[x.\s,%+\-Rp$]*$/.test(val)) {
                    shouldRun = true;
                    break;
                }
            }
        }
        if (shouldRun) {
            observer.disconnect();
            runTransformation();
            observer.observe(document.body, { childList: true, subtree: true, characterData: true });
        }
    });

    observer.observe(document.body, { childList: true, subtree: true, characterData: true });
})();
