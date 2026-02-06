(function () {
    const style = document.createElement('style');
    style.textContent = `
        .sas-floating-toolbar {
            position: absolute;
            background: linear-gradient(135deg, rgba(30, 30, 35, 0.98), rgba(20, 20, 25, 0.98));
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 14px;
            padding: 6px;
            display: flex;
            gap: 3px;
            z-index: 100000;
            box-shadow: 
                0 8px 32px rgba(0, 0, 0, 0.4),
                0 2px 8px rgba(0, 0, 0, 0.2),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
            opacity: 0;
            visibility: hidden;
            transform: translateY(12px) scale(0.92);
            transition: all 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
            pointer-events: none;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        .sas-floating-toolbar.active {
            opacity: 1;
            visibility: visible;
            transform: translateY(0) scale(1);
            pointer-events: all;
            animation: toolbarEntrance 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        
        @keyframes toolbarEntrance {
            0% {
                opacity: 0;
                transform: translateY(12px) scale(0.92);
            }
            60% {
                transform: translateY(-2px) scale(1.02);
            }
            100% {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        .sas-floating-toolbar button {
            position: relative;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.08);
            color: rgba(255, 255, 255, 0.85);
            width: 38px;
            height: 38px;
            cursor: pointer;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
            padding: 0;
        }
        
        .sas-floating-toolbar button.format-active {
            background: rgba(96, 165, 250, 0.25);
            border-color: rgba(96, 165, 250, 0.5);
            color: #60a5fa;
            box-shadow: 0 0 0 2px rgba(96, 165, 250, 0.15);
        }
        
        .sas-floating-toolbar button.format-active::after {
            content: '';
            position: absolute;
            top: 3px;
            right: 3px;
            width: 6px;
            height: 6px;
            background: #60a5fa;
            border-radius: 50%;
            box-shadow: 0 0 8px rgba(96, 165, 250, 0.8);
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.6; transform: scale(0.9); }
        }
        
        .sas-floating-toolbar button svg {
            width: 18px;
            height: 18px;
            transition: all 0.2s;
        }
        
        .sas-floating-toolbar button::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.15);
            transform: translate(-50%, -50%);
            transition: width 0.4s, height 0.4s;
        }
        
        .sas-floating-toolbar button:hover {
            background: rgba(255, 255, 255, 0.12);
            border-color: rgba(255, 255, 255, 0.2);
            color: #ffffff;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .sas-floating-toolbar button.format-active:hover {
            background: rgba(96, 165, 250, 0.35);
            border-color: rgba(96, 165, 250, 0.6);
        }
        
        .sas-floating-toolbar button:hover svg {
            transform: scale(1.1);
        }
        
        .sas-floating-toolbar button:active::before {
            width: 100px;
            height: 100px;
        }
        
        .sas-floating-toolbar button:active {
            transform: scale(0.95);
        }
        
        .sas-floating-toolbar .divider {
            width: 1px;
            background: linear-gradient(
                to bottom,
                rgba(255, 255, 255, 0),
                rgba(255, 255, 255, 0.15),
                rgba(255, 255, 255, 0)
            );
            margin: 8px 4px;
            align-self: stretch;
        }
        
        .sas-floating-toolbar::after {
            content: '';
            position: absolute;
            bottom: -7px;
            left: 50%;
            transform: translateX(-50%);
            border-left: 7px solid transparent;
            border-right: 7px solid transparent;
            border-top: 7px solid rgba(30, 30, 35, 0.98);
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
        }
        
        .sas-floating-toolbar.arrow-left::after {
            left: 20px;
            transform: translateX(0);
        }
        
        .sas-floating-toolbar.arrow-right::after {
            left: auto;
            right: 20px;
            transform: translateX(0);
        }
        
        .sas-floating-toolbar button .tooltip {
            position: absolute;
            bottom: -36px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.95);
            color: white;
            padding: 6px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 500;
            white-space: nowrap;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.2s;
            z-index: 1;
        }
        
        .sas-floating-toolbar button:hover .tooltip {
            opacity: 1;
            animation: tooltipFade 0.2s ease;
        }
        
        .sas-floating-toolbar button .tooltip.with-shortcut {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        
        .sas-floating-toolbar button .tooltip .shortcut {
            background: rgba(255, 255, 255, 0.15);
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 10px;
            font-family: monospace;
        }
        
        @keyframes tooltipFade {
            from { opacity: 0; transform: translateX(-50%) translateY(-4px); }
            to { opacity: 1; transform: translateX(-50%) translateY(0); }
        }
        
        .sas-toolbar-status {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: rgba(0, 0, 0, 0.9);
            color: white;
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 12px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            z-index: 99999;
            opacity: 0;
            transform: translateY(10px);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            pointer-events: none;
        }
        
        .sas-toolbar-status.show {
            opacity: 1;
            transform: translateY(0);
        }
        
        .sas-toolbar-status.success {
            background: rgba(16, 185, 129, 0.95);
        }
        
        .sas-toolbar-status.removed {
            background: rgba(239, 68, 68, 0.95);
        }
    `;
    document.head.appendChild(style);

    const icons = {
        bold: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M6 4h8a4 4 0 0 1 0 8H6z"/>
            <path d="M6 12h9a4 4 0 0 1 0 8H6z"/>
        </svg>`,

        italic: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="19" y1="4" x2="10" y2="4"/>
            <line x1="14" y1="20" x2="5" y2="20"/>
            <line x1="15" y1="4" x2="9" y2="20"/>
        </svg>`,

        underline: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M6 3v7a6 6 0 0 0 12 0V3"/>
            <line x1="4" y1="21" x2="20" y2="21"/>
        </svg>`,

        strikethrough: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M16 4H9a3 3 0 0 0-2.83 4"/>
            <path d="M14 12a4 4 0 0 1 0 8H6"/>
            <line x1="4" y1="12" x2="20" y2="12"/>
        </svg>`,

        quote: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 21c3 0 7-1 7-8V5c0-1.25-.756-2.017-2-2H4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2 1 0 1 0 1 1v1c0 1-1 2-2 2s-1 .008-1 1.031V20c0 1 0 1 1 1z"/>
            <path d="M15 21c3 0 7-1 7-8V5c0-1.25-.757-2.017-2-2h-4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2h.75c0 2.25.25 4-2.75 4v3c0 1 0 1 1 1z"/>
        </svg>`,

        undo: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="1 4 1 10 7 10"/>
            <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/>
        /svg>`,

        redo: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="23 4 23 10 17 10"/>
            <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/>
        </svg>`
    };
    
    const toolbar = document.createElement('div');
    toolbar.className = 'sas-floating-toolbar';
    
    const isMac = navigator.platform.toUpperCase().indexOf('MAC') >= 0;
    const modKey = isMac ? '⌘' : 'Ctrl';
    
    toolbar.innerHTML = `
        <button data-tag="b">
            ${icons.bold}
            <span class="tooltip with-shortcut">
                <span>Bold</span>
                <span class="shortcut">${modKey}+B</span>
            </span>
        </button>
        <button data-tag="i">
            ${icons.italic}
            <span class="tooltip with-shortcut">
                <span>Italic</span>
                <span class="shortcut">${modKey}+I</span>
            </span>
        </button>
        <div class="divider"></div>
        <button data-tag="u">
            ${icons.underline}
            <span class="tooltip with-shortcut">
                <span>Underline</span>
                <span class="shortcut">${modKey}+U</span>
            </span>
        </button>
        <button data-tag="s">
            ${icons.strikethrough}
            <span class="tooltip">
                <span>Strikethrough</span>
            </span>
        </button>
        <div class="divider"></div>
        <button data-tag="quote">
            ${icons.quote}
            <span class="tooltip">
                <span>Quote</span>
            </span>
        </button>
        <div class="divider"></div>
        <button data-action="undo">
            ${icons.undo}
            <span class="tooltip with-shortcut">
                <span>Undo</span>
                <span class="shortcut">${modKey}+Z</span>
            </span>
        </button>
        <button data-action="redo">
            ${icons.redo}
            <span class="tooltip with-shortcut">
                <span>Redo</span>
                <span class="shortcut">${modKey}+${isMac ? '⇧+Z' : 'Y'}</span>
            </span>
        </button>
    `;
    document.body.appendChild(toolbar);

    const statusDiv = document.createElement('div');
    statusDiv.className = 'sas-toolbar-status';
    document.body.appendChild(statusDiv);

    let activeInput = null;
    let hideTimeout = null;
    let statusTimeout = null;
    let undoStack = new Map();
    let isUndoRedo = false;

    const showStatus = (message, type = 'success') => {
        clearTimeout(statusTimeout);
        statusDiv.textContent = message;
        statusDiv.className = `sas-toolbar-status show ${type}`;
        
        statusTimeout = setTimeout(() => {
            statusDiv.classList.remove('show');
        }, 2000);
    };

    const getInputHistory = (input) => {
        if (!undoStack.has(input)) {
            undoStack.set(input, {
                history: [{
                    value: input.value,
                    start: input.selectionStart,
                    end: input.selectionEnd
                }],
                position: 0
            });
        }
        return undoStack.get(input);
    };

    const saveToHistory = (input) => {
        if (isUndoRedo) return;
        
        const history = getInputHistory(input);
        const currentValue = input.value;
        const lastEntry = history.history[history.position];
        
        if (lastEntry && lastEntry.value === currentValue) return;
        
        history.history = history.history.slice(0, history.position + 1);
        
        history.history.push({
            value: currentValue,
            start: input.selectionStart,
            end: input.selectionEnd
        });
        
        if (history.history.length > 100) {
            history.history.shift();
        } else {
            history.position++;
        }
    };

    const performUndo = () => {
        if (!activeInput) return;
        
        const history = getInputHistory(activeInput);
        
        if (history.position <= 0) {
            showStatus('Nothing to undo', 'removed');
            return;
        }
        
        isUndoRedo = true;
        
        history.position--;
        const state = history.history[history.position];
        
        activeInput.value = state.value;
        activeInput.setSelectionRange(state.start, state.end);
        
        activeInput.dispatchEvent(new Event('input', { bubbles: true }));
        activeInput.dispatchEvent(new Event('change', { bubbles: true }));
        
        setTimeout(() => {
            isUndoRedo = false;
        }, 50);
        
        showStatus('Undo', 'removed');
        updateButtonStates();
    };

    const performRedo = () => {
        if (!activeInput) return;
        
        const history = getInputHistory(activeInput);
        
        if (history.position >= history.history.length - 1) {
            showStatus('Nothing to redo', 'removed');
            return;
        }
        
        isUndoRedo = true;
        
        history.position++;
        const state = history.history[history.position];
        
        activeInput.value = state.value;
        activeInput.setSelectionRange(state.start, state.end);
        
        activeInput.dispatchEvent(new Event('input', { bubbles: true }));
        activeInput.dispatchEvent(new Event('change', { bubbles: true }));
        
        setTimeout(() => {
            isUndoRedo = false;
        }, 50);
        
        showStatus('Redo', 'success');
        updateButtonStates();
    };

    const detectActiveTags = (text, start, end) => {
        const selectedText = text.substring(start, end);
        const activeTags = new Set();
        
        const tags = ['b', 'i', 'u', 's', 'quote'];
        
        for (const tag of tags) {
            const openTag = `[${tag}]`;
            const closeTag = `[/${tag}]`;
            
            if (selectedText.startsWith(openTag) && selectedText.endsWith(closeTag)) {
                activeTags.add(tag);
            }
            
            const beforeSelection = text.substring(0, start);
            const afterSelection = text.substring(end);
            
            const openBefore = (beforeSelection.match(new RegExp(`\\[${tag}\\]`, 'g')) || []).length;
            const closeBefore = (beforeSelection.match(new RegExp(`\\[/${tag}\\]`, 'g')) || []).length;
            
            if (openBefore > closeBefore) {
                if (afterSelection.includes(closeTag)) {
                    activeTags.add(tag);
                }
            }
        }
        
        return activeTags;
    };

    const updateButtonStates = () => {
        if (!activeInput) return;
        
        const start = activeInput.selectionStart;
        const end = activeInput.selectionEnd;
        const value = activeInput.value;
        
        const activeTags = detectActiveTags(value, start, end);
        
        toolbar.querySelectorAll('button[data-tag]').forEach(btn => {
            if (activeTags.has(btn.dataset.tag)) {
                btn.classList.add('format-active');
            } else {
                btn.classList.remove('format-active');
            }
        });
    };

    const formatText = (tag) => {
        if (!activeInput) return;

        const start = activeInput.selectionStart;
        const end = activeInput.selectionEnd;
        const value = activeInput.value;
        const selectedText = value.substring(start, end);

        if (!selectedText) return;

        saveToHistory(activeInput);

        const openTag = `[${tag}]`;
        const closeTag = `[/${tag}]`;

        let replacement;
        let newSelectionStart, newSelectionEnd;
        let action = 'added';

        if (selectedText.startsWith(openTag) && selectedText.endsWith(closeTag)) {
            replacement = selectedText.substring(openTag.length, selectedText.length - closeTag.length);
            newSelectionStart = start;
            newSelectionEnd = start + replacement.length;
            action = 'removed';
        } 
        else {
            const beforeSelection = value.substring(0, start);
            const afterSelection = value.substring(end);
            
            const tagJustBefore = beforeSelection.endsWith(openTag);
            const tagJustAfter = afterSelection.startsWith(closeTag);
            
            if (tagJustBefore && tagJustAfter) {
                const newValue = 
                    beforeSelection.substring(0, beforeSelection.length - openTag.length) +
                    selectedText +
                    afterSelection.substring(closeTag.length);
                
                activeInput.value = newValue;
                newSelectionStart = start - openTag.length;
                newSelectionEnd = end - openTag.length;
                action = 'removed';
                
                activeInput.setSelectionRange(newSelectionStart, newSelectionEnd);
                activeInput.dispatchEvent(new Event('input', { bubbles: true }));
                activeInput.dispatchEvent(new Event('change', { bubbles: true }));
                
                updateButtonStates();
                showStatus(`${tag.toUpperCase()} formatting removed`, 'removed');
                return;
            }
            
            let searchStart = start;
            
            while (searchStart < end) {
                const segmentStart = value.indexOf(openTag, searchStart);
                if (segmentStart === -1 || segmentStart >= end) break;
                
                const segmentEnd = value.indexOf(closeTag, segmentStart);
                if (segmentEnd === -1 || segmentEnd > end) break;
                
                const beforeTag = value.substring(0, start);
                const afterTag = value.substring(end);
                let middlePart = selectedText;
                
                const tagRegex = new RegExp(`\\[${tag}\\]([\\s\\S]*?)\\[/${tag}\\]`, 'g');
                middlePart = middlePart.replace(tagRegex, '$1');
                
                middlePart = middlePart.replace(new RegExp(`\\[${tag}\\]`, 'g'), '');
                middlePart = middlePart.replace(new RegExp(`\\[/${tag}\\]`, 'g'), '');
                
                activeInput.value = beforeTag + middlePart + afterTag;
                newSelectionStart = start;
                newSelectionEnd = start + middlePart.length;
                action = 'removed';
                
                activeInput.setSelectionRange(newSelectionStart, newSelectionEnd);
                activeInput.dispatchEvent(new Event('input', { bubbles: true }));
                activeInput.dispatchEvent(new Event('change', { bubbles: true }));
                
                updateButtonStates();
                showStatus(`${tag.toUpperCase()} formatting removed`, 'removed');
                return;
            }
            
            replacement = openTag + selectedText + closeTag;
            newSelectionStart = start;
            newSelectionEnd = end + openTag.length + closeTag.length;
            action = 'added';
        }

        const scrollTop = activeInput.scrollTop;

        activeInput.focus();
        activeInput.setSelectionRange(start, end);
        document.execCommand('insertText', false, replacement);

        activeInput.setSelectionRange(newSelectionStart, newSelectionEnd);
        activeInput.scrollTop = scrollTop;

        activeInput.dispatchEvent(new Event('input', { bubbles: true }));
        activeInput.dispatchEvent(new Event('change', { bubbles: true }));
        
        saveToHistory(activeInput);
        
        updateButtonStates();
        
        const actionText = action === 'added' ? 'applied' : 'removed';
        const statusType = action === 'added' ? 'success' : 'removed';
        showStatus(`${tag.toUpperCase()} formatting ${actionText}`, statusType);
    };

    const positionToolbar = (x, y) => {
        const toolbarWidth = toolbar.offsetWidth;
        const toolbarHeight = toolbar.offsetHeight;
        const padding = 12;
        const arrowOffset = 18;

        let left = x - (toolbarWidth / 2);
        let top = y - toolbarHeight - arrowOffset;

        const viewportWidth = window.innerWidth;
        const scrollTop = window.pageYOffset;

        toolbar.classList.remove('arrow-left', 'arrow-right');

        if (left < padding) {
            left = padding;
            toolbar.classList.add('arrow-left');
        } else if (left + toolbarWidth > viewportWidth - padding) {
            left = viewportWidth - toolbarWidth - padding;
            toolbar.classList.add('arrow-right');
        }

        if (top < scrollTop + padding) {
            top = y + arrowOffset;
        }

        toolbar.style.left = `${left}px`;
        toolbar.style.top = `${top}px`;
    };

    const showToolbar = () => {
        clearTimeout(hideTimeout);
        toolbar.classList.add('active');
        updateButtonStates();
    };

    const hideToolbar = (immediate = false) => {
        clearTimeout(hideTimeout);
        if (immediate) {
            toolbar.classList.remove('active');
        } else {
            hideTimeout = setTimeout(() => {
                toolbar.classList.remove('active');
            }, 100);
        }
    };

    let selectionTimeout;

    const handleSelection = (e) => {
        clearTimeout(selectionTimeout);

        selectionTimeout = setTimeout(() => {
            const isTextInput = e.target.tagName === 'TEXTAREA' ||
                (e.target.tagName === 'INPUT' && e.target.type === 'text');

            if (!isTextInput) {
                if (!e.target.closest('.sas-floating-toolbar')) {
                    hideToolbar(true);
                }
                return;
            }

            const start = e.target.selectionStart;
            const end = e.target.selectionEnd;
            const hasSelection = start !== end;

            if (hasSelection) {
                activeInput = e.target;
                
                const rect = e.target.getBoundingClientRect();
                const x = e.pageX || (rect.left + rect.width / 2);
                const y = e.pageY || (rect.top + window.pageYOffset);
                
                positionToolbar(x, y);
                showToolbar();
            } else {
                if (!e.target.closest('.sas-floating-toolbar')) {
                    hideToolbar(true);
                }
            }
        }, 10);
    };

    document.addEventListener('mouseup', handleSelection);

    document.addEventListener('focus', (e) => {
        const isTextInput = e.target.tagName === 'TEXTAREA' ||
            (e.target.tagName === 'INPUT' && e.target.type === 'text');
        
        if (isTextInput) {
            activeInput = e.target;
            if (!undoStack.has(e.target)) {
                getInputHistory(e.target);
            }
        }
    }, true);

    document.addEventListener('input', (e) => {
        const isTextInput = e.target.tagName === 'TEXTAREA' ||
            (e.target.tagName === 'INPUT' && e.target.type === 'text');
        
        if (isTextInput && !isUndoRedo) {
            saveToHistory(e.target);
        }
    });

    document.addEventListener('keyup', (e) => {
        if (e.shiftKey) {
            const target = document.activeElement;
            const isTextInput = target.tagName === 'TEXTAREA' ||
                (target.tagName === 'INPUT' && target.type === 'text');
            
            if (isTextInput && target.selectionStart !== target.selectionEnd) {
                activeInput = target;
                const rect = target.getBoundingClientRect();
                positionToolbar(
                    rect.left + rect.width / 2,
                    rect.top + window.pageYOffset
                );
                showToolbar();
            }
        }
    });

    document.addEventListener('contextmenu', (e) => {
        const isTextInput = e.target.tagName === 'TEXTAREA' ||
            (e.target.tagName === 'INPUT' && e.target.type === 'text');

        if (isTextInput) {
            setTimeout(() => {
                const start = e.target.selectionStart;
                const end = e.target.selectionEnd;
                if (start !== end) {
                    e.preventDefault();
                    handleSelection(e);
                }
            }, 10);
        }
    });

    toolbar.querySelectorAll('button[data-tag]').forEach(btn => {
        btn.addEventListener('mousedown', (e) => {
            e.preventDefault();
            e.stopPropagation();
            formatText(btn.dataset.tag);
        });
    });

    toolbar.querySelectorAll('button[data-action]').forEach(btn => {
        btn.addEventListener('mousedown', (e) => {
            e.preventDefault();
            e.stopPropagation();
            
            if (btn.dataset.action === 'undo') {
                performUndo();
            } else if (btn.dataset.action === 'redo') {
                performRedo();
            }
        });
    });

    document.addEventListener('keydown', (e) => {
        const target = document.activeElement;
        const isTextInput = target.tagName === 'TEXTAREA' ||
            (target.tagName === 'INPUT' && target.type === 'text');
        
        if (!isTextInput) return;

        const isMac = navigator.platform.toUpperCase().indexOf('MAC') >= 0;
        const modKey = isMac ? e.metaKey : e.ctrlKey;

        if (!modKey) return;

        const key = e.key.toLowerCase();
        
        if (key === 'z' && !e.shiftKey) {
            e.preventDefault();
            activeInput = target;
            performUndo();
            return;
        }
        
        if ((key === 'y' && !isMac) || (key === 'z' && e.shiftKey && isMac)) {
            e.preventDefault();
            activeInput = target;
            performRedo();
            return;
        }

        let tag = null;
        switch (key) {
            case 'b': tag = 'b'; break;
            case 'i': tag = 'i'; break;
            case 'u': tag = 'u'; break;
        }

        if (tag && target.selectionStart !== target.selectionEnd) {
            e.preventDefault();
            activeInput = target;
            formatText(tag);
        }
    });

    document.addEventListener('selectionchange', () => {
        if (activeInput && toolbar.classList.contains('active')) {
            updateButtonStates();
        }
    });

    window.addEventListener('scroll', () => hideToolbar(true), { passive: true });
    window.addEventListener('resize', () => hideToolbar(true), { passive: true });

    toolbar.addEventListener('mousedown', (e) => e.stopPropagation());

    document.addEventListener('mousedown', (e) => {
        if (!e.target.closest('.sas-floating-toolbar') && 
            !e.target.closest('.sas-toolbar-status') && 
            e.target !== activeInput) {
            hideToolbar(true);
        }
    });

})();