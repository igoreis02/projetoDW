// Assim que a página carregar, eu começo a preparar minhas funções.
document.addEventListener('DOMContentLoaded', () => {
    // --- MINHAS VARIÁVEIS E REFERÊNCIAS ---
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
        const filters = {
            city: citySelect.value,
            startDate: startDateInput.value,
            endDate: endDateInput.value,
            reportType: reportTypeSelect.value,
            status: statusSelect.value
        };
        currentReportType = filters.reportType;

        generateReportBtn.textContent = 'Gerando...';
        generateReportBtn.disabled = true;
        modalErrorMessage.classList.add('hidden');

        try {
            const params = new URLSearchParams(filters);
            const response = await fetch(`API/generate_report.php?${params.toString()}`);
            const result = await response.json();

            if (result.success && Object.keys(result.data).length > 0) {
                currentReportData = result.data;
                currentReportHeaders = result.headers;
                
                // Eu verifico o tipo de relatório para decidir como desenhar a tela
                if (currentReportType === 'matriz_tecnica' || currentReportType === 'controle_ocorrencia') {
                    renderReportList(result.data); // Chamo minha nova função de lista
                } else {
                    // Para os outros relatórios, eu transformo os dados agrupados de volta em uma lista simples
                    const flatData = Object.values(result.data).flat();
                    renderReportTable(result.headers, flatData); // E chamo a função de tabela que eu havia apagado
                }

                reportResultContainer.classList.remove('hidden');
                closeModal('reportFiltersModal');
            } else {
                modalErrorMessage.textContent = result.message || 'Nenhum dado encontrado para estes filtros.';
                modalErrorMessage.classList.remove('hidden');
                reportResultContainer.classList.add('hidden');
            }
        } catch (error) {
            modalErrorMessage.textContent = 'Erro de comunicação com o servidor.';
            modalErrorMessage.classList.remove('hidden');
        } finally {
            generateReportBtn.textContent = 'Gerar';
            generateReportBtn.disabled = false;
        }
    }

    // --- [FUNÇÃO ATUALIZADA] Para renderizar o relatório no novo formato de lista ---
    function renderReportList(groupedData) {
        reportContentContainer.innerHTML = ''; // Eu limpo a área de resultados
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
                                <th colspan="4">MANUTENÇÃO/REPARO</th>
                            </tr>
                            <tr>
                                <th>Data</th>
                                <th>DESCRIÇÃO PROBLEMA</th>
                                <th>Data</th>
                                <th>DESCRIÇÃO REPARO</th>
                                <th>ATENDIDO EM (DIAS)</th>
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
    
    // --- [FUNÇÃO RESTAURADA] A função para renderizar tabelas simples está de volta ---
    function renderReportTable(headers, data) {
        // Eu começo a construir o HTML da minha tabela.
        let tableHTML = '<table class="maintenance-table">';

        // Eu crio o cabeçalho (<thead>) da tabela.
        tableHTML += '<thead><tr>';
        headers.forEach(header => {
            tableHTML += `<th>${header}</th>`;
        });
        tableHTML += '</tr></thead>';

        // Agora, eu crio o corpo (<tbody>) da tabela, linha por linha.
        tableHTML += '<tbody>';
        data.forEach(row => {
            tableHTML += '<tr>';
            // Para cada linha, eu pego os valores na mesma ordem dos cabeçalhos.
            headers.forEach(header => {
                const value = row[header] !== null && row[header] !== undefined ? row[header] : '-';
                tableHTML += `<td>${value}</td>`;
            });
            tableHTML += '</tr>';
        });
        tableHTML += '</tbody></table>';
        
        // Eu coloco a tabela pronta dentro do container na página.
        reportContentContainer.innerHTML = tableHTML;
    }

    // --- Função de download do Excel ---
    function downloadExcel() {
        if (Object.keys(currentReportData).length === 0) {
            alert("Não há dados para exportar.");
            return;
        }
        
        // Se não for Matriz Técnica ou Controle, eu uso o download de tabela simples
        if (currentReportType !== 'matriz_tecnica' && currentReportType !== 'controle_ocorrencia') {
            const flatData = Object.values(currentReportData).flat();
            const worksheet = XLSX.utils.json_to_sheet(flatData);
            const workbook = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(workbook, worksheet, "Relatorio");
            XLSX.writeFile(workbook, "Relatorio_Simples.xlsx");
            return;
        }

        // Se for o relatório detalhado, eu continuo com a lógica complexa
        const wb = XLSX.utils.book_new();
        const borderAll = { top: { style: "thin" }, bottom: { style: "thin" }, left: { style: "thin" }, right: { style: "thin" } };
        const cityHeaderStyle = { font: { bold: true, sz: 14 }, alignment: { horizontal: "center", vertical: "center" }, border: borderAll };
        const equipmentHeaderStyle = { font: { bold: true, sz: 12 }, alignment: { horizontal: "left", vertical: "center" }, border: borderAll };
        const tableHeaderStyle = { font: { bold: true }, alignment: { horizontal: "center", vertical: "center" }, border: borderAll };
        const descriptionCellStyle = { border: borderAll, alignment: { horizontal: "center", vertical: "center", wrapText: true } };
        const defaultDataCellStyle = { border: borderAll, alignment: { horizontal: "left", vertical: "center", wrapText: true } };

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
            merges.push({ s: { r: 0, c: 0 }, e: { r: 0, c: 6 } });
            aoa.push([]);
            rowIndex = 2;

            for (const equipmentName in equipmentGroups) {
                aoa.push([`Equipamento: ${equipmentName}`]);
                merges.push({ s: { r: rowIndex, c: 0 }, e: { r: rowIndex, c: 6 } });
                rowIndex++;
                aoa.push(["Item", "OS - ORDEM DE SERVIÇO", null, "MANUTENÇÃO/REPARO", null, null, null]);
                merges.push({ s: { r: rowIndex, c: 0 }, e: { r: rowIndex + 1, c: 0 } });
                merges.push({ s: { r: rowIndex, c: 1 }, e: { r: rowIndex, c: 2 } });
                merges.push({ s: { r: rowIndex, c: 3 }, e: { r: rowIndex, c: 6 } });
                rowIndex++;
                aoa.push([null, "Data", "DESCRIÇÃO PROBLEMA", "Data", "DESCRIÇÃO REPARO", "ATENDIDO EM (DIAS)", "Técnico"]);
                rowIndex++;
                equipmentGroups[equipmentName].forEach((record, index) => {
                    aoa.push([
                        index + 1,
                        record['Data Início'] || '-',
                        record['Descrição Problema'] || '-',
                        record['Data Fim'] || '-',
                        record['Descrição Reparo'] || '-',
                        record['Atendido em dia(s)'] !== null ? record['Atendido em dia(s)'] : '-',
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
                for (let C = 0; C < aoa[R].length; ++C) {
                    const cell_address = { c: C, r: R };
                    const cell_ref = XLSX.utils.encode_cell(cell_address);
                    if (!ws[cell_ref]) ws[cell_ref] = { t: 's', v: '' };
                    let styleToApply;
                    const cellValue = aoa[R][C];
                    if (R === 0) {
                        styleToApply = cityHeaderStyle;
                    } else if (cellValue && cellValue.toString().startsWith('Equipamento:')) {
                        styleToApply = equipmentHeaderStyle;
                    } else if ((aoa[R] && aoa[R].includes("Item")) || (aoa[R - 1] && aoa[R - 1].includes("Item"))) {
                        styleToApply = tableHeaderStyle;
                    } else if (C === 2 || C === 4) {
                        styleToApply = descriptionCellStyle;
                    } else {
                        styleToApply = defaultDataCellStyle;
                    }
                    ws[cell_ref].s = styleToApply;
                }
            }
            ws['!cols'] = [{ wch: 5 }, { wch: 18 }, { wch: 45 }, { wch: 18 }, { wch: 45 }, { wch: 15 }, { wch: 30 }];
            XLSX.utils.book_append_sheet(wb, ws, city.substring(0, 31));
        }

        XLSX.writeFile(wb, "Relatorio_Detalhado_DeltaWay.xlsx");
    }

    window.openModal = (modalId) => document.getElementById(modalId).classList.add('is-active');
    window.closeModal = (modalId) => document.getElementById(modalId).classList.remove('is-active');
});