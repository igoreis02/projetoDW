<?php
session_start(); // Inicia ou resume a sessão
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
} else {
    // Redireciona para a página de login
    header("Location: index.html");
    exit;
}
$_SESSION['last_access'] = time(); // Atualiza o timestamp do último acesso

$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['user_email'];


$redefinir_senha_obrigatoria = isset($_SESSION['redefinir_senha_obrigatoria']) && $_SESSION['redefinir_senha_obrigatoria'] === true;

?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/manutencoesInstalacoes.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="icon" type="image/png" href="imagens/favicon.png">
    <title>Ocorrências e Instalações</title>
   
</head>

<body>
    <div class="background"></div>
    <div class="card">
        <div class="header-container">
            <a href="menu.php" class="back-btn-icon" title="Voltar ao Menu">&larr;</a>
            <h2>Ocorrências e Instalações</h2>
            <a href="menu.php" class="close-btn" title="Voltar ao Menu">&times;</a>
        </div>

        <div class="page-buttons-container">
            <button id="matrizManutencaoBtn" class="page-button"><i class="fas fa-cogs"></i> Matriz Técnica</button>
            <button id="matrizSemaforicaBtn" class="page-button"><i class="fas fa-traffic-light"></i> Matriz
                Semafórica</button>
            <button id="controleOcorrenciaBtn" class="page-button"><i class="fas fa-clipboard-list"></i> Controle de
                Ocorrência</button>
            <button id="instalarEquipamentoBtn" class="page-button"><i class="fas fa-tools"></i> Instalar
                equipamento</button>
        </div>

        <a href="menu.php" class="voltar-btn">Voltar ao Menu</a>
    </div>
    <div class="footer">
        <p>&copy; 2025 APsystem. Todos os direitos reservados.</p>
    </div>

    <?php if ($redefinir_senha_obrigatoria): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                window.location.href = 'index.html';
            });
        </script>
    <?php endif; ?>

    <div id="cadastroManutencaoModal" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeCadastroManutencaoModal()">&times;</span>
            <h3 id="modalTitle">Cadastrar Ocorrência</h3>

            <div id="citySelectionSection">
                <p>Selecione a cidade:</p>
                <div id="cityButtonsContainer" class="city-buttons-container">
                    <p id="loadingCitiesMessage">Carregando...</p>
                </div>
                <p id="cityErrorMessage" class="message error hidden"></p>
            </div>

            <div id="equipmentSelectionSection" class="equipment-selection-container">
                <button class="back-button" onclick="goBackToCitySelection()">&larr;</button>
                <p>Selecione o equipamento:</p>
                <input type="text" id="equipmentSearchInput" placeholder="Digite para filtrar...">
                <select id="equipmentSelect" size="5"></select>
                <p id="loadingEquipmentMessage" class="hidden"></p>
                <p id="equipmentErrorMessage" class="message error hidden"></p>

                <div id="problemDescriptionSection" class="problem-description-container">
                    <label for="problemDescription">Descrição do problema:</label>
                    <textarea id="problemDescription" rows="4" placeholder="Descreva o problema..."></textarea>
                </div>

                <div id="realizadoPorSection" class="choice-container">
                    <label>Realizado por:</label>
                    <div class="choice-buttons">
                        <button id="btnProcessamento" class="page-button">Processamento</button>
                        <button id="btnProvedor" class="page-button">Provedor</button>
                    </div>
                </div>

                <div id="reparoConcluidoSection" class="choice-container">
                    <label>Concluído o reparo?</label>
                    <div class="choice-buttons">
                        <button id="btnReparoSim" class="page-button">Sim</button>
                        <button id="btnReparoNao" class="page-button">Não</button>
                    </div>
                </div>

                <div id="tecnicoInLocoSection" class="choice-container">
                    <label>Precisa de técnico In Loco:</label>
                    <div class="choice-buttons">
                        <button id="btnTecnicoSim" class="page-button">Sim</button>
                        <button id="btnTecnicoNao" class="page-button">Não</button>
                    </div>
                </div>

                <div id="repairDescriptionSection" class="problem-description-container">
                    <label for="repairDescription">Descrição do reparo:</label>
                    <textarea id="repairDescription" rows="4" placeholder="Descreva o reparo realizado..."></textarea>
                </div>

                <span id="equipmentSelectionErrorMessage" class="selection-error-message hidden"></span>
                <button id="confirmEquipmentSelection" class="page-button" style="margin-top: 1rem;">
                    Avançar <span id="selectionSpinner" class="loading-spinner hidden"></span>
                </button>
            </div>

            <div id="installEquipmentAndAddressSection" class="install-equipment-section">
                <button class="back-button" onclick="goBackToCitySelection()">&larr;</button>
                <h4>Dados do Novo Equipamento</h4>

                <label for="newEquipmentType">Tipo de Equipamento:</label>
                <select id="newEquipmentType">
                    <option value="">-- Selecione o Tipo --</option>
                    <option value="CCO">CCO</option>
                    <option value="RADAR FIXO">RADAR FIXO</option>
                    <option value="DOME">DOME</option>
                    <option value="EDUCATIVO">EDUCATIVO</option>
                    <option value="LOMBADA">LOMBADA</option>
                </select>

                <label for="newEquipmentName">Nome / Identificador:</label>
                <input type="text" id="newEquipmentName" placeholder="Ex: MT300, D22">

                <label for="newEquipmentReference">Referência / Local:</label>
                <input type="text" id="newEquipmentReference" placeholder="Ex: Próximo à Escola">

                <div id="quantitySection" class="hidden" style="display: flex; flex-direction: column; gap: 15px;">
                    <label for="newEquipmentQuantity">Quantidade de Faixas:</label>
                    <input type="number" id="newEquipmentQuantity" placeholder="Ex: 2" min="1">
                </div>

                <h5>Dados do Endereço:</h5>
                <label for="addressLogradouro">Logradouro:</label>
                <input type="text" id="addressLogradouro" placeholder="Ex: Av. Principal, Qd. 10">

                <label for="addressBairro">Bairro:</label>
                <input type="text" id="addressBairro" placeholder="Ex: Centro">

                <label for="addressCep">CEP:</label>
                <input type="text" id="addressCep" placeholder="Ex: 12345-678">

                <label for="addressLatitude">Latitude (Opcional):</label>
                <input type="number" step="any" id="addressLatitude" placeholder="-16.75854">

                <label for="addressLongitude">Longitude (Opcional):</label>
                <input type="number" step="any" id="addressLongitude" placeholder="-49.25569">

                <label for="installationNotes">Observação da Instalação (Opcional):</label>
                <textarea id="installationNotes" rows="3" placeholder="Informação adicional..."></textarea>

                <button id="confirmInstallEquipment" class="page-button" style="margin-top: 1rem;">Avançar para
                    Confirmação</button>
            </div>
            <div id="semaforicaSection" class="install-equipment-section"
                style="flex-direction: column; gap: 15px; margin-top: 20px; text-align: left;">
                <button class="back-button" onclick="goBackToCitySelection()">&larr;</button>
                <form id="semaforicaForm">
                    <label for="semaforicaTipo">Tipo:</label>
                    <select id="semaforicaTipo">
                        <option value="Não_definido">-- Selecione o Tipo --</option>
                        <option value="instalacao">Instalação</option>
                        <option value="manutencao">Manutenção</option>
                        <option value="retirada">Retirada</option>
                    </select>

                    <label for="semaforicaEndereco">Endereço:</label>
                    <textarea id="semaforicaEndereco" rows="3" placeholder="Ex: Av. Brasil com Rua 10"></textarea>

                    <label for="semaforicaReferencia">Referência (Opcional):</label>
                    <input type="text" id="semaforicaReferencia" placeholder="Ex: Em frente ao supermercado">

                    <label for="semaforicaQtd">Quantidade:</label>
                    <input type="number" id="semaforicaQtd" value="1" min="1">

                    <label for="semaforicaUnidade">Unidade:</label>
                    <select id="semaforicaUnidade">
                        <option value="unidade">Unidade</option>
                        <option value="metros">Metros</option>
                        <option value="kit">Kit</option>
                    </select>

                    <label for="semaforicaDescricao">Descrição do Problema:</label>
                    <textarea id="semaforicaDescricao" rows="4"
                        placeholder="Descreva o problema ou a necessidade..."></textarea>

                    <label for="semaforicaGeo">Geolocalização (URL Google Maps - Opcional):</label>
                    <input type="text" id="semaforicaGeo" placeholder="Cole a URL aqui">

                    <label for="semaforicaObservacao">Observação (Opcional):</label>
                    <textarea id="semaforicaObservacao" rows="3"
                        placeholder="Insira qualquer observação adicional..."></textarea>

                    <p id="semaforicaErrorMessage" class="message error hidden"></p>
                    <button type="button" id="confirmSemaforicaBtn" class="page-button" style="margin-top: 1rem;">
                        Avançar <span id="semaforicaSpinner" class="loading-spinner hidden"></span>
                    </button>
                </form>
            </div>
        </div>
    </div>


    <div id="pendingMaintenanceModal" class="modal">
        <div class="modal-content">
            <h3>Aviso de Manutenção Pendente</h3>
            <p>Esse equipamento já tem manutenção cadastrada.</p>

            <p class="modal-ocorrencia-existente">
                <strong>Ocorrência(s) existente(s):</strong>
                <span id="existingMaintenanceText" style="font-style: italic;"></span>
            </p>

            <p><strong>Deseja adicionar o novo problema a esta ocorrência?</strong></p>

            <div class="confirmation-buttons">
                <button class="confirm-button page-button" id="confirmAppendProblem">Sim</button>
                <button class="cancel-button page-button" id="cancelAppendProblem">Não</button>
            </div>
        </div>
    </div>

    <div id="confirmationModal" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeConfirmationModal()">&times;</span>
            <h3 id="modalTitleConfirm">Confirmação da Operação</h3>
            <div class="confirmation-details">
                <p><strong>Cidade:</strong> <span id="confirmCityName"></span></p>

                <div id="maintenanceConfirmationDetails" class="hidden">
                    <p><strong>Equipamento:</strong> <span id="confirmEquipmentName"></span></p>
                    <p><strong>Problema:</strong> <span id="confirmProblemDescription"></span></p>
                    <p id="confirmRepairDescriptionContainer" class="hidden"><strong>Reparo Realizado:</strong> <span
                            id="confirmRepairDescription"></span></p>
                </div>

                <div id="installConfirmationDetails" class="hidden">
                    <h4>Detalhes do Novo Equipamento:</h4>
                    <p><strong>Tipo:</strong> <span id="confirmEquipmentType"></span></p>
                    <p><strong>Nome:</strong> <span id="confirmNewEquipmentName"></span></p>
                    <p><strong>Referência:</strong> <span id="confirmNewEquipmentRef"></span></p>

                    <p id="confirmQuantityContainer" class="hidden"><strong>Qtd. Faixas:</strong> <span
                            id="confirmEquipmentQuantity"></span></p>
                    <p><strong>Logradouro:</strong> <span id="confirmAddressLogradouro"></span></p>
                    <p><strong>Bairro:</strong> <span id="confirmAddressBairro"></span></p>
                    <p><strong>CEP:</strong> <span id="confirmAddressCep"></span></p>
                    <p><strong>Observação:</strong> <span id="confirmInstallationNotes"></span></p>
                </div>

                <div id="semaforicaConfirmationDetails" class="hidden">
                    <h4>Detalhes da Ocorrência Semafórica:</h4>
                    <p><strong>Tipo:</strong> <span id="confirmSemaforicaTipo"></span></p>
                    <p><strong>Endereço:</strong> <span id="confirmSemaforicaEndereco"></span></p>
                    <p><strong>Referência:</strong> <span id="confirmSemaforicaReferencia"></span></p>
                    <p><strong>Quantidade:</strong> <span id="confirmSemaforicaQtd"></span> (<span
                            id="confirmSemaforicaUnidade"></span>)</p>
                    <p><strong>Problema:</strong> <span id="confirmSemaforicaDescricao"></span></p>
                    <p><strong>Geolocalização:</strong> <span id="confirmSemaforicaGeo"></span></p>
                    <p><strong>Observação:</strong> <span id="confirmSemaforicaObservacao"></span></p>
                    <div id="confirmSemaforicaAssignmentDetails" class="hidden"
                        style="margin-top: 1rem; padding-top: 1rem; border-top: 1px dashed #ccc;">
                        <p><strong>Técnicos Atribuídos:</strong> <span id="confirmSemaforicaTecnicos"
                                style="font-weight: normal;"></span></p>
                        <p><strong>Veículos Atribuídos:</strong> <span id="confirmSemaforicaVeiculos"
                                style="font-weight: normal;"></span></p>
                    </div>
                </div>


                <div id="confirmProviderContainer" class="hidden">
                    <p><strong>Problema:</strong> <span id="confirmProviderProblem"></span></p>

                    <p id="confirmProviderAcao">
                        <strong>Ação:</strong> <span>Atribuído ao provedor <strong id="confirmProviderName"></strong>,
                            aguardando manutenção.</span>
                    </p>

                    <p id="confirmProviderReparo" class="hidden">
                        <strong>Reparo Realizado:</strong> <span id="confirmProviderReparoText"></span>
                    </p>
                </div>

                <p><strong>Tipo de Operação:</strong> <span id="confirmMaintenanceType"></span></p>
                <p><strong>Status Inicial:</strong> <span id="confirmRepairStatus"></span></p>
            </div>
            <div class="confirmation-buttons">
                <button class="page-button hidden" id="assignTecnicoSemaforicaBtn">Atribuir Técnico</button>

                <button class="confirm-button page-button" id="confirmSaveButton">
                    Confirmar <span id="confirmSpinner" class="loading-spinner hidden"></span>
                </button>
                <button class="cancel-button page-button" id="cancelSaveButton"
                    onclick="cancelSaveManutencao()">Cancelar</button>
            </div>
            <p id="confirmMessage" class="message hidden"></p>
        </div>
    </div>
    <<div id="assignSemaforicaModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Atribuir Técnico à Ocorrência</h3>
                <button class="close-button" onclick="closeModal('assignSemaforicaModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group" style="display: none;">
                    <label>Período de Execução</label>
                    <div>
                        <input type="date" id="assignInicioReparo">
                        <input type="date" id="assignFimReparo">
                    </div>
                </div>

                <div class="form-group">
                    <label>Técnicos (pode selecionar mais de um)</label>
                    <div id="assignTecnicosContainer" class="choice-buttons-column"></div>
                </div>
                <div class="form-group">
                    <label>Veículos (pode selecionar mais de um)</label>
                    <div id="assignVeiculosContainer" class="choice-buttons-grid"></div>
                </div>
                <p id="assignErrorMessage" class="message error hidden"></p>
            </div>
            <div class="confirmation-buttons">
                <button id="saveAssignmentBtn" class="page-button" onclick="saveSemaforicaAssignment()">
                    <span>Confirmar Atribuição</span>
                    <span class="loading-spinner hidden"></span>
                </button>
                <button class="cancel-button page-button"
                    onclick="closeModal('assignSemaforicaModal')">Cancelar</button>
            </div>
        </div>
        </div>

        <script src="js/manutencoes_instalacoes.js"></script>
</body>

</html>