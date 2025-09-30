// Assim que a página carregar, eu começo a preparar minhas funções.
document.addEventListener('DOMContentLoaded', () => {
    // --- MINHAS REFERÊNCIAS AOS ELEMENTOS DA PÁGINA ---
    const openReportModalBtn = document.getElementById('openReportModalBtn');
    const generateReportBtn = document.getElementById('generateReportBtn');
    const downloadExcelBtn = document.getElementById('downloadExcelBtn');
    const reportFiltersModal = document.getElementById('reportFiltersModal');
    const reportResultContainer = document.getElementById('reportResultContainer');
    const reportContentContainer = document.getElementById('reportTable');
    const startDateInput = document.getElementById('startDate');
    const endDateInput = document.getElementById('endDate');
    const citySelect = document.getElementById('citySelect');
    const reportTypeSelect = document.getElementById('reportTypeSelect');
    const statusSelect = document.getElementById('statusSelect');
    const modalErrorMessage = document.getElementById('modalErrorMessage');

    // --- MINHAS VARIÁVEIS DE ESTADO ---
    let currentReportData = {};
    let currentReportHeaders = [];
    let currentReportType = 'matriz_tecnica';

    // --- MEUS EVENTOS DE CLIQUE E INTERAÇÕES ---
    openReportModalBtn.addEventListener('click', () => {
        modalErrorMessage.classList.add('hidden');
        reportFiltersModal.classList.add('is-active');
        populateCityFilter();
    });
    generateReportBtn.addEventListener('click', generateReport);
    downloadExcelBtn.addEventListener('click', downloadExcel);
    startDateInput.addEventListener('change', () => {
        endDateInput.min = startDateInput.value;
    });

    // --- MINHAS FUNÇÕES ---
    async function populateCityFilter() {
        citySelect.innerHTML = '<option value="">Carregando cidades...</option>';
        try {
            const response = await fetch('API/get_cidades.php');
            const data = await response.json();
            citySelect.innerHTML = '<option value="todos">Todas as Cidades</option>';
            if (data.success) {
                data.cidades.forEach(city => {
                    const option = document.createElement('option');
                    option.value = city.id_cidade;
                    option.textContent = city.nome;
                    citySelect.appendChild(option);
                });
            }
        } catch (error) {
            citySelect.innerHTML = '<option value="">Erro ao carregar</option>';
        }
    }

    async function generateReport() {
        // [MUDANÇA AQUI] Eu pego todos os status selecionados
        const selectedStatus = Array.from(statusSelect.selectedOptions).map(option => option.value);

        const filters = {
            city: citySelect.value,
            startDate: startDateInput.value,
            endDate: endDateInput.value,
            reportType: reportTypeSelect.value
        };
        currentReportType = filters.reportType;

        generateReportBtn.textContent = 'Gerando...';
        generateReportBtn.disabled = true;
        modalErrorMessage.classList.add('hidden');

        try {
            const params = new URLSearchParams(filters);
            // [MUDANÇA AQUI] Eu adiciono cada status selecionado aos parâmetros da URL
            selectedStatus.forEach(status => params.append('status[]', status));

            const response = await fetch(`API/generate_report.php?${params.toString()}`);
            const result = await response.json();

            if (result.success && Object.keys(result.data).length > 0) {
                currentReportData = result.data;
                currentReportHeaders = result.headers;

                if (currentReportType === 'matriz_tecnica' || currentReportType === 'controle_ocorrencia') {
                    renderReportList(result.data);
                } else {
                    // Para relatórios simples, eu uso a chave "geral" que criei no PHP
                    renderReportTable(result.headers, result.data.geral);
                }

                reportResultContainer.classList.remove('hidden');
                closeModal('reportFiltersModal');
            } else {
                modalErrorMessage.textContent = result.message || 'Nenhum dado encontrado para estes filtros.';
                modalErrorMessage.classList.remove('hidden');
                reportResultContainer.classList.add('hidden');
            }
        } catch (error) {
            console.error("Erro ao gerar relatório:", error);
            modalErrorMessage.textContent = 'Erro de comunicação com o servidor.';
            modalErrorMessage.classList.remove('hidden');
        } finally {
            generateReportBtn.textContent = 'Gerar';
            generateReportBtn.disabled = false;
        }
    }

    // [FUNÇÃO ATUALIZADA] Adicionada a coluna de Status
    function renderReportList(groupedData) {
        reportContentContainer.innerHTML = '';
        for (const city in groupedData) {
            const cityHeader = document.createElement('h2');
            cityHeader.className = 'report-city-header';
            cityHeader.textContent = city;
            reportContentContainer.appendChild(cityHeader);

            const equipmentGroups = {};
            groupedData[city].forEach(record => {
                const equipName = record.Equipamento;
                if (!equipmentGroups[equipName]) {
                    equipmentGroups[equipName] = [];
                }
                equipmentGroups[equipName].push(record);
            });

            for (const equipmentName in equipmentGroups) {
                const blockDiv = document.createElement('div');
                blockDiv.className = 'equipment-report-block';

                blockDiv.innerHTML = `
                    <p class="equipment-name">Equipamento: ${equipmentName}</p>
                    <table class="maintenance-table">
                        <thead>
                            <tr>
                                <th rowspan="2">Item</th>
                                <th colspan="2">OS - ORDEM DE SERVIÇO</th>
                                <th colspan="5">MANUTENÇÃO/REPARO</th> 
                            </tr>
                            <tr>
                                <th>Data</th>
                                <th>DESCRIÇÃO PROBLEMA</th>
                                <th>Data</th>
                                <th>DESCRIÇÃO REPARO</th>
                                <th>ATENDIDO EM (DIAS)</th>
                                <th>Status</th>
                                <th>Técnico</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${equipmentGroups[equipmentName].map((record, index) => `
                                <tr>
                                    <td>${index + 1}</td>
                                    <td>${record['Data Início'] || '-'}</td>
                                    <td>${record['Descrição Problema'] || '-'}</td>
                                    <td>${record['Data Fim'] || '-'}</td>
                                    <td>${record['Descrição Reparo'] || '-'}</td>
                                    <td>${record['Atendido em dia(s)'] !== null ? record['Atendido em dia(s)'] : '-'}</td>
                                    <td>${record['Status'] || '-'}</td>
                                    <td>${record['Técnico'] || '-'}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                `;
                reportContentContainer.appendChild(blockDiv);
            }
        }
    }

    function renderReportTable(headers, data) {
        let tableHTML = '<table class="maintenance-table">';
        tableHTML += '<thead><tr>';
        headers.forEach(header => { tableHTML += `<th>${header}</th>`; });
        tableHTML += '</tr></thead>';
        tableHTML += '<tbody>';
        data.forEach(row => {
            tableHTML += '<tr>';
            headers.forEach(header => {
                const value = row[header] !== null && row[header] !== undefined ? row[header] : '-';
                tableHTML += `<td>${value}</td>`;
            });
            tableHTML += '</tr>';
        });
        tableHTML += '</tbody></table>';
        reportContentContainer.innerHTML = tableHTML;
    }

    // [FUNÇÃO ATUALIZADA] Lógica completa para gerar o Excel com a nova coluna de Status
    function downloadExcel() {
        if (Object.keys(currentReportData).length === 0) {
            alert("Não há dados para exportar.");
            return;
        }

        if (currentReportType !== 'matriz_tecnica' && currentReportType !== 'controle_ocorrencia') {
            const worksheet = XLSX.utils.json_to_sheet(currentReportData.geral);
            const workbook = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(workbook, worksheet, "Relatorio");
            XLSX.writeFile(workbook, "Relatorio_Simples.xlsx");
            return;
        }

        const wb = XLSX.utils.book_new();
        
        const borderAll = { top: { style: "thin" }, bottom: { style: "thin" }, left: { style: "thin" }, right: { style: "thin" } };
        const cityHeaderStyle = { font: { bold: true, sz: 14 }, alignment: { horizontal: "center", vertical: "center" }, border: borderAll };
        const equipmentHeaderStyle = { font: { bold: true, sz: 12 }, alignment: { horizontal: "left", vertical: "center" }, border: borderAll };
        const tableHeaderStyle = { font: { bold: true }, alignment: { horizontal: "center", vertical: "center", wrapText: true }, border: borderAll };
        const defaultDataCellStyle = { border: borderAll, alignment: { horizontal: "center", vertical: "center", wrapText: true } };
        const descriptionDataCellStyle = { border: borderAll, alignment: { horizontal: "left", vertical: "top", wrapText: true } };

        for (const city in currentReportData) {
            let aoa = [];
            let merges = [];
            let rowIndex = 0;

            const equipmentGroups = {};
            currentReportData[city].forEach(record => {
                const equipName = record.Equipamento;
                if (!equipmentGroups[equipName]) equipmentGroups[equipName] = [];
                equipmentGroups[equipName].push(record);
            });

            aoa.push([city]);
            merges.push({ s: { r: 0, c: 0 }, e: { r: 0, c: 7 } }); // Merge para 8 colunas
            aoa.push([]); 
            rowIndex = 2;

            for (const equipmentName in equipmentGroups) {
                aoa.push([`Equipamento: ${equipmentName}`]);
                merges.push({ s: { r: rowIndex, c: 0 }, e: { r: rowIndex, c: 7 } }); // Merge para 8 colunas
                rowIndex++;
                
                // Cabeçalho Nível 1
                aoa.push(["Item", "OS - ORDEM DE SERVIÇO", null, "MANUTENÇÃO/REPARO", null, null, null, null]);
                merges.push({ s: { r: rowIndex, c: 0 }, e: { r: rowIndex + 1, c: 0 } }); // Item
                merges.push({ s: { r: rowIndex, c: 1 }, e: { r: rowIndex, c: 2 } });     // OS
                merges.push({ s: { r: rowIndex, c: 3 }, e: { r: rowIndex, c: 7 } });     // Manutenção (agora com 5 colunas)
                rowIndex++;

                // Cabeçalho Nível 2
                aoa.push([null, "Data", "DESCRIÇÃO PROBLEMA", "Data", "DESCRIÇÃO REPARO", "ATENDIDO EM (DIAS)", "Status", "Técnico"]);
                rowIndex++;

                // Dados
                equipmentGroups[equipmentName].forEach((record, index) => {
                    aoa.push([
                        index + 1,
                        record['Data Início'] || '-',
                        record['Descrição Problema'] || '-',
                        record['Data Fim'] || '-',
                        record['Descrição Reparo'] || '-',
                        record['Atendido em dia(s)'] !== null ? record['Atendido em dia(s)'] : '-',
                        record['Status'] || '-',
                        record['Técnico'] || '-'
                    ]);
                    rowIndex++;
                });
                aoa.push([]); 
                rowIndex++;
            }

            const ws = XLSX.utils.aoa_to_sheet(aoa);
            ws['!merges'] = merges;

            for (let R = 0; R < aoa.length; ++R) {
                for (let C = 0; C < 8; ++C) { // Loop até 8 colunas
                    const cell_address = { c: C, r: R };
                    const cell_ref = XLSX.utils.encode_cell(cell_address);
                    
                    if (!ws[cell_ref]) ws[cell_ref] = { t: 's', v: '' };
                    
                    let styleToApply = {};
                    const cellValue = (aoa[R] && aoa[R][C] !== undefined && aoa[R][C] !== null) ? String(aoa[R][C]) : "";
                    const isDataRow = aoa[R] && aoa[R].length > 1 && typeof aoa[R][0] === 'number';

                    if (R === 0) {
                        styleToApply = cityHeaderStyle;
                    } else if (cellValue.startsWith('Equipamento:')) {
                        styleToApply = equipmentHeaderStyle;
                    } else if ( (aoa[R] && aoa[R].includes("Item")) || (aoa[R-1] && aoa[R-1].includes("Item")) ) {
                        styleToApply = tableHeaderStyle;
                    } else if (isDataRow) {
                        if (C === 2 || C === 4) { // Colunas de Descrição
                            styleToApply = descriptionDataCellStyle; 
                        } else { // Outras colunas de dados
                            styleToApply = defaultDataCellStyle; 
                        }
                    }
                    
                    ws[cell_ref].s = styleToApply;
                }
            }
            
            // Definição da largura das colunas (adicionada uma para Status)
            ws['!cols'] = [{ wch: 5 }, { wch: 15 }, { wch: 40 }, { wch: 15 }, { wch: 40 }, { wch: 15 }, { wch: 15 }, { wch: 25 }];
            
            XLSX.utils.book_append_sheet(wb, ws, city.substring(0, 31));
        }

        XLSX.writeFile(wb, "Relatorio_Detalhado_DeltaWay.xlsx");
    }
    
    window.openModal = (modalId) => document.getElementById(modalId).classList.add('is-active');
    window.closeModal = (modalId) => document.getElementById(modalId).classList.remove('is-active');
});