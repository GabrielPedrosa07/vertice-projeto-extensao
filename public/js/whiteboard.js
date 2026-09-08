/**
 * VÉRTICE — Quadro Branco Digital Colaborativo (Whiteboard / Canvas)
 * Ferramenta interativa de diagramação (UML, DER, Fluxogramas) com sincronização em tempo real
 */

(function () {
    let canvas = null;
    let ctx = null;
    let currentChannelId = 1;
    let currentTool = 'pencil'; // pencil, line, arrow, rect, circle, text, eraser
    let currentColor = '#4ecdc4';
    let currentLineWidth = 3;
    let isDrawing = false;
    let startX = 0;
    let startY = 0;
    let snapshot = null;
    let shapes = [];
    let currentPencilPoints = [];
    let lastLoadedShapeId = 0;
    let syncInterval = null;

    function initWhiteboard() {
        canvas = document.getElementById('whiteboard-canvas');
        if (!canvas) return;

        ctx = canvas.getContext('2d');
        resizeCanvas();

        window.addEventListener('resize', resizeCanvas);

        const channelMeta = document.querySelector('[data-channel-id]');
        currentChannelId = channelMeta ? parseInt(channelMeta.getAttribute('data-channel-id'), 10) : 1;

        // Tool buttons
        document.querySelectorAll('.wb-tool-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                document.querySelectorAll('.wb-tool-btn').forEach(b => b.classList.remove('active'));
                const target = e.currentTarget;
                target.classList.add('active');
                currentTool = target.getAttribute('data-tool');
            });
        });

        // Color buttons
        document.querySelectorAll('.wb-color-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                document.querySelectorAll('.wb-color-btn').forEach(b => b.classList.remove('active'));
                const target = e.currentTarget;
                target.classList.add('active');
                currentColor = target.getAttribute('data-color');
            });
        });

        // Width selector
        const widthSelect = document.getElementById('wb-line-width');
        if (widthSelect) {
            widthSelect.addEventListener('change', (e) => {
                currentLineWidth = parseInt(e.target.value, 10);
            });
        }

        // Actions
        const btnClear = document.getElementById('btn-clear-wb');
        if (btnClear) btnClear.addEventListener('click', clearWhiteboard);

        const btnDownload = document.getElementById('btn-download-wb');
        if (btnDownload) btnDownload.addEventListener('click', downloadWhiteboardImage);

        // Canvas Events
        canvas.addEventListener('mousedown', onMouseDown);
        canvas.addEventListener('mousemove', onMouseMove);
        canvas.addEventListener('mouseup', onMouseUp);
        canvas.addEventListener('mouseleave', onMouseUp);

        // Touch support para tablets/celulares
        canvas.addEventListener('touchstart', onTouchStart, { passive: false });
        canvas.addEventListener('touchmove', onTouchMove, { passive: false });
        canvas.addEventListener('touchend', onTouchEnd, { passive: false });

        // Carrega formas iniciais do servidor
        loadWhiteboardFromServer();

        if (syncInterval) clearInterval(syncInterval);
        syncInterval = setInterval(syncWhiteboardFromServer, 2500);
    }

    function resizeCanvas() {
        if (!canvas) return;
        const rect = canvas.getBoundingClientRect();
        if (rect.width === 0 || rect.height === 0) return;

        // Ajuste de DPI
        const dpr = window.devicePixelRatio || 1;
        canvas.width = rect.width * dpr;
        canvas.height = rect.height * dpr;
        ctx.scale(dpr, dpr);

        redrawAllShapes();
    }

    function getCanvasCoords(e) {
        const rect = canvas.getBoundingClientRect();
        return {
            x: e.clientX - rect.left,
            y: e.clientY - rect.top
        };
    }

    function onMouseDown(e) {
        const coords = getCanvasCoords(e);
        startX = coords.x;
        startY = coords.y;
        isDrawing = true;

        if (currentTool === 'text') {
            handleTextTool(startX, startY);
            isDrawing = false;
            return;
        }

        if (currentTool === 'pencil' || currentTool === 'eraser') {
            currentPencilPoints = [{ x: startX, y: startY }];
            ctx.beginPath();
            ctx.moveTo(startX, startY);
        } else {
            // Salva snapshot para animação de arraste de formas (retângulo, círculo, seta)
            snapshot = ctx.getImageData(0, 0, canvas.width, canvas.height);
        }
    }

    function onMouseMove(e) {
        if (!isDrawing) return;
        const coords = getCanvasCoords(e);

        if (currentTool === 'pencil' || currentTool === 'eraser') {
            currentPencilPoints.push(coords);
            ctx.strokeStyle = currentTool === 'eraser' ? '#070c18' : currentColor;
            ctx.lineWidth = currentTool === 'eraser' ? currentLineWidth * 4 : currentLineWidth;
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
            ctx.lineTo(coords.x, coords.y);
            ctx.stroke();
        } else {
            // Restaura snapshot para evitar borrões
            if (snapshot) ctx.putImageData(snapshot, 0, 0);

            drawShapePreview(currentTool, startX, startY, coords.x, coords.y, currentColor, currentLineWidth);
        }
    }

    function onMouseUp(e) {
        if (!isDrawing) return;
        isDrawing = false;

        const coords = getCanvasCoords(e);
        let newShape = null;

        if (currentTool === 'pencil' || currentTool === 'eraser') {
            newShape = {
                type: 'path',
                tool: currentTool,
                color: currentTool === 'eraser' ? '#070c18' : currentColor,
                lineWidth: currentTool === 'eraser' ? currentLineWidth * 4 : currentLineWidth,
                points: currentPencilPoints
            };
        } else if (currentTool === 'line' || currentTool === 'arrow' || currentTool === 'rect' || currentTool === 'circle') {
            newShape = {
                type: currentTool,
                startX: startX,
                startY: startY,
                endX: coords.x,
                endY: coords.y,
                color: currentColor,
                lineWidth: currentLineWidth
            };
        }

        if (newShape) {
            shapes.push(newShape);
            saveShapeToServer(newShape);
        }
    }

    function onTouchStart(e) {
        e.preventDefault();
        const touch = e.touches[0];
        const mouseEvent = new MouseEvent('mousedown', {
            clientX: touch.clientX,
            clientY: touch.clientY
        });
        canvas.dispatchEvent(mouseEvent);
    }

    function onTouchMove(e) {
        e.preventDefault();
        const touch = e.touches[0];
        const mouseEvent = new MouseEvent('mousemove', {
            clientX: touch.clientX,
            clientY: touch.clientY
        });
        canvas.dispatchEvent(mouseEvent);
    }

    function onTouchEnd(e) {
        e.preventDefault();
        const mouseEvent = new MouseEvent('mouseup', {});
        canvas.dispatchEvent(mouseEvent);
    }

    function handleTextTool(x, y) {
        const text = prompt('Digite o texto ou anotação para o diagrama (ex: Nome da Tabela, [PK] id, Atributos):');
        if (!text || !text.trim()) return;

        const newShape = {
            type: 'text',
            text: text.trim(),
            x: x,
            y: y,
            color: currentColor,
            fontSize: currentLineWidth >= 5 ? 18 : 14
        };

        shapes.push(newShape);
        drawShape(newShape);
        saveShapeToServer(newShape);
    }

    function drawShapePreview(tool, x1, y1, x2, y2, color, width) {
        ctx.strokeStyle = color;
        ctx.fillStyle = color;
        ctx.lineWidth = width;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';

        ctx.beginPath();
        if (tool === 'line') {
            ctx.moveTo(x1, y1);
            ctx.lineTo(x2, y2);
            ctx.stroke();
        } else if (tool === 'rect') {
            const w = x2 - x1;
            const h = y2 - y1;
            ctx.strokeRect(x1, y1, w, h);
        } else if (tool === 'circle') {
            const radius = Math.sqrt(Math.pow(x2 - x1, 2) + Math.pow(y2 - y1, 2));
            ctx.arc(x1, y1, radius, 0, 2 * Math.PI);
            ctx.stroke();
        } else if (tool === 'arrow') {
            drawArrow(x1, y1, x2, y2, color, width);
        }
    }

    function drawArrow(fromx, fromy, tox, toy, color, width) {
        const headlen = 12 + width * 2;
        const dx = tox - fromx;
        const dy = toy - fromy;
        const angle = Math.atan2(dy, dx);

        ctx.strokeStyle = color;
        ctx.fillStyle = color;
        ctx.lineWidth = width;

        ctx.beginPath();
        ctx.moveTo(fromx, fromy);
        ctx.lineTo(tox, toy);
        ctx.stroke();

        ctx.beginPath();
        ctx.moveTo(tox, toy);
        ctx.lineTo(tox - headlen * Math.cos(angle - Math.PI / 6), toy - headlen * Math.sin(angle - Math.PI / 6));
        ctx.lineTo(tox - headlen * Math.cos(angle + Math.PI / 6), toy - headlen * Math.sin(angle + Math.PI / 6));
        ctx.closePath();
        ctx.fill();
    }

    function drawShape(s) {
        if (!ctx) return;
        ctx.strokeStyle = s.color || '#4ecdc4';
        ctx.fillStyle = s.color || '#4ecdc4';
        ctx.lineWidth = s.lineWidth || 3;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';

        if (s.type === 'path' && s.points && s.points.length > 0) {
            ctx.beginPath();
            ctx.moveTo(s.points[0].x, s.points[0].y);
            for (let i = 1; i < s.points.length; i++) {
                ctx.lineTo(s.points[i].x, s.points[i].y);
            }
            ctx.stroke();
        } else if (s.type === 'line') {
            ctx.beginPath();
            ctx.moveTo(s.startX, s.startY);
            ctx.lineTo(s.endX, s.endY);
            ctx.stroke();
        } else if (s.type === 'rect') {
            ctx.strokeRect(s.startX, s.startY, s.endX - s.startX, s.endY - s.startY);
        } else if (s.type === 'circle') {
            const radius = Math.sqrt(Math.pow(s.endX - s.startX, 2) + Math.pow(s.endY - s.startY, 2));
            ctx.beginPath();
            ctx.arc(s.startX, s.startY, radius, 0, 2 * Math.PI);
            ctx.stroke();
        } else if (s.type === 'arrow') {
            drawArrow(s.startX, s.startY, s.endX, s.endY, s.color, s.lineWidth);
        } else if (s.type === 'text') {
            ctx.font = `bold ${s.fontSize || 14}px 'Inter', sans-serif`;
            ctx.fillText(s.text, s.x, s.y);
        }
    }

    function redrawAllShapes() {
        if (!ctx || !canvas) return;
        const rect = canvas.getBoundingClientRect();
        ctx.clearRect(0, 0, rect.width, rect.height);
        shapes.forEach(drawShape);
    }

    async function saveShapeToServer(shape) {
        try {
            await fetch('/api/tools/whiteboard/save', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    channel_id: currentChannelId,
                    shape_data: shape
                })
            });
        } catch (err) {}
    }

    async function loadWhiteboardFromServer() {
        try {
            const res = await fetch(`/api/tools/whiteboard/get?channel_id=${currentChannelId}`);
            const data = await res.json();
            if (data.status === 'success' && data.shapes) {
                shapes = [];
                data.shapes.forEach(row => {
                    lastLoadedShapeId = Math.max(lastLoadedShapeId, row.id);
                    try {
                        const parsed = typeof row.shape_data === 'string' ? JSON.parse(row.shape_data) : row.shape_data;
                        shapes.push(parsed);
                    } catch (e) {}
                });
                redrawAllShapes();
            }
        } catch (err) {}
    }

    async function syncWhiteboardFromServer() {
        if (!currentChannelId || isDrawing) return;
        try {
            const res = await fetch(`/api/tools/whiteboard/get?channel_id=${currentChannelId}`);
            const data = await res.json();
            if (data.status === 'success' && data.shapes) {
                let hasNew = false;
                data.shapes.forEach(row => {
                    if (row.id > lastLoadedShapeId) {
                        lastLoadedShapeId = row.id;
                        try {
                            const parsed = typeof row.shape_data === 'string' ? JSON.parse(row.shape_data) : row.shape_data;
                            shapes.push(parsed);
                            hasNew = true;
                        } catch (e) {}
                    }
                });
                if (hasNew) {
                    redrawAllShapes();
                }
            }
        } catch (err) {}
    }

    async function clearWhiteboard() {
        if (!confirm('Deseja realmente limpar todo o quadro branco desta sala?')) return;
        try {
            await fetch('/api/tools/whiteboard/clear', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ channel_id: currentChannelId })
            });
            shapes = [];
            lastLoadedShapeId = 0;
            redrawAllShapes();
        } catch (err) {
            alert('Erro ao limpar o quadro branco.');
        }
    }

    function downloadWhiteboardImage() {
        if (!canvas) return;
        // Cria um canvas temporário com fundo escuro sólido para download elegante
        const tempCanvas = document.createElement('canvas');
        tempCanvas.width = canvas.width;
        tempCanvas.height = canvas.height;
        const tempCtx = tempCanvas.getContext('2d');

        tempCtx.fillStyle = '#070c18';
        tempCtx.fillRect(0, 0, tempCanvas.width, tempCanvas.height);
        tempCtx.drawImage(canvas, 0, 0);

        const a = document.createElement('a');
        a.download = `diagrama_vertice_${new Date().getTime()}.png`;
        a.href = tempCanvas.toDataURL('image/png');
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    }

    window.initWhiteboard = initWhiteboard;
    document.addEventListener('DOMContentLoaded', () => {
        if (document.getElementById('whiteboard-canvas')) {
            initWhiteboard();
        }
    });
})();
