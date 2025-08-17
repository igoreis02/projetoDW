<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit();
}

// --- Configurações do Banco de Dados ---
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "gerenciamento_manutencoes";

// --- Conexão com o Banco de Dados ---
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

// --- Variáveis para armazenar os dados ---
$ocorrencias_por_cidade = [];
$cidades_com_ocorrencias = [];
$errorMessage = '';

try {
    // --- Consulta SQL para buscar ocorrências em andamento (Manutenções e Instalações) ---
    $sql = "SELECT
                m.id_manutencao,
                m.tipo_manutencao,
                m.ocorrencia_reparo,
                m.inicio_reparo,
                m.status_reparo,
                m.inst_laco, m.dt_laco,
                m.inst_base, m.dt_base,
                m.inst_infra, m.data_infra,
                m.inst_energia, m.dt_energia,
                e.nome_equip,
                e.referencia_equip,
                c.nome AS cidade,
                CONCAT(en.logradouro, ', ', en.bairro) AS local_completo,
                GROUP_CONCAT(DISTINCT u.nome SEPARATOR ', ') AS tecnicos_nomes,
                MIN(mt.inicio_reparoTec) AS inicio_periodo_reparo,
                MAX(mt.fim_reparoT) AS fim_periodo_reparo
            FROM manutencoes AS m
            JOIN equipamentos AS e ON m.id_equipamento = e.id_equipamento
            JOIN cidades AS c ON m.id_cidade = c.id_cidade
            LEFT JOIN endereco AS en ON e.id_endereco = en.id_endereco
            LEFT JOIN manutencoes_tecnicos AS mt ON m.id_manutencao = mt.id_manutencao
            LEFT JOIN usuario AS u ON mt.id_tecnico = u.id_usuario -- Assumindo que a tabela de técnicos é 'usuarios'
            WHERE m.status_reparo = 'em andamento'
            GROUP BY m.id_manutencao
            ORDER BY c.nome, m.inicio_reparo DESC";

    $result = $conn->query($sql);

    if ($result === false) {
        throw new Exception("Erro na consulta SQL: " . $conn->error);
    }

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $cidade = $row['cidade'];
            // Agrupa as ocorrências por cidade
            if (!isset($ocorrencias_por_cidade[$cidade])) {
                $ocorrencias_por_cidade[$cidade] = [];
            }
            $ocorrencias_por_cidade[$cidade][] = $row;
            
            // Cria uma lista de cidades para os botões de filtro
            if (!in_array($cidade, $cidades_com_ocorrencias)) {
                $cidades_com_ocorrencias[] = $cidade;
            }
        }
        sort($cidades_com_ocorrencias);
    } else {
        $errorMessage = 'Nenhuma ocorrência em andamento encontrada.';
    }

} catch (Exception $e) {
    $errorMessage = 'Erro ao carregar dados: ' . $e->getMessage();
    error_log("Erro em manutencao_emAndamento.php: " . $e->getMessage());
} finally {
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ocorrências em Andamento</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Estilos Gerais */
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f0f2f5;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            box-sizing: border-box;
        }

        /* Card Principal */
        .card {
            background-color: #ffffff;
            padding: 2.5rem;
            border-radius: 1rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            width: 95%;
            max-width: 1200px;
            text-align: center;
            position: relative;
        }

        /* Título e Botões de Navegação */
        .header-container {
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            margin-bottom: 2rem;
        }
        .header-container h2 {
            font-size: 2.2em;
            color: var(--cor-principal);
            margin: 0;
        }
        .close-btn, .back-btn-icon {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            font-size: 2em;
            font-weight: bold;
            color: #aaa;
            text-decoration: none;
            transition: color 0.3s;
        }
        .close-btn { right: 0; }
        .back-btn-icon { left: 0; }
        .close-btn:hover, .back-btn-icon:hover { color: #333; }

        /* Botões de Ação (Manutenção/Instalação) */
        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .action-btn {
            padding: 12px 25px;
            font-size: 1.1em;
            font-weight: 600;
            color: var(--cor-principal);
            background-color: #eef2ff;
            border: 2px solid transparent;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .action-btn:hover {
            background-color: #e0e7ff;
        }
        .action-btn.active {
            background-color: var(--cor-principal);
            color: white;
        }

        /* Filtros de Cidade */
        .filter-container {
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 0.8rem;
        }
        .filter-btn {
            padding: 8px 18px;
            font-size: 0.9em;
            color: #4b5563;
            background-color: #f3f4f6;
            border: 1px solid #d1d5db;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .filter-btn:hover {
            background-color: #e5e7eb;
        }
        .filter-btn.active {
            background-color: #6366f1;
            color: white;
            border-color: #6366f1;
        }

        /* NOVO: Container de Grupos de Cidade */
        .ocorrencias-container {
            width: 100%;
        }
        .city-group {
            margin-bottom: 2.5rem;
        }
        .city-group-title {
            font-size: 1.8em;
            color: #374151;
            text-align: left;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--cor-principal);
        }
        .city-ocorrencias-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
        }

        /* Item de Ocorrência Individual */
        .ocorrencia-item {
            background-color: #ffffff; /* Fundo branco para destaque */
            border: 1px solid #e5e7eb;
            border-left: 5px solid var(--cor-principal);
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06); /* Sombra para destaque */
            transition: box-shadow 0.3s, transform 0.3s;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }
        .ocorrencia-item[data-type="instalação"] {
            border-left-color: #f97316; /* Laranja para instalações */
        }
        .ocorrencia-item:hover {
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transform: translateY(-3px);
        }
        
        /* Layout do Cabeçalho do Item */
        .ocorrencia-header {
            text-align: left;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px dashed #d1d5db;
        }
        .ocorrencia-header h3 {
            font-size: 1.3em;
            color: #111827;
            margin: 0;
        }
        .ocorrencia-header .cidade {
            font-size: 1.1em;
            font-weight: 500;
            color: #4b5563;
            margin: 0.25rem 0 0 0;
        }
        
        /* Layout dos Detalhes do Item */
        .ocorrencia-details {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 0.8rem;
            flex-grow: 1;
        }
        .detail-item {
            font-size: 0.95em;
            color: #374151;
            line-height: 1.5;

        }
        .detail-item strong {
            font-weight: 600;
            color: #1f2937;
        }
        .detail-item strong::after {
            content: ": ";
        }
        .detail-item span {
            word-break: break-word;
        }
        .detail-item.stacked strong {
            display: block;
        }
        .status-em-andamento {
            background-color: #fffbeb;
            color: #f59e0b;
            padding: 2px 8px;
            border-radius: 15px;
            font-weight: 600;
            font-size: 0.9em;
        }
        .status-value.instalado {
            color: #16a34a;
            font-weight: 600;
        }
        .status-value.aguardando {
            color: #ef4444;
            font-weight: 600;
        }

        /* Botão Voltar */
        .voltar-btn {
            display: inline-block;
            width: auto;
            min-width: 200px;
            padding: 15px 30px;
            margin-top: 3rem;
            text-align: center;
            background-color: var(--botao-voltar);
            color: var(--cor-letra-botaoVoltar);
            text-decoration: none;
            border-radius: 8px;
            font-size: 1.1em;
            transition: background-color 0.3s ease;
        }
        .voltar-btn:hover {
            background-color: var(--botao-voltar-hover);
        }

        .hidden { display: none !important; }

        /* Responsividade */
        @media (max-width: 992px) {
            .city-ocorrencias-grid {
                grid-template-columns: 1fr; /* Uma coluna para tablets e abaixo */
            }
        }
        @media (max-width: 768px) {
            .card { padding: 1.5rem; }
            .header-container h2 { font-size: 1.8em; }
            .action-buttons, .filter-container { flex-direction: column; }
        }
    </style>
</head>
<body>
    <div class="background"></div>
    <div class="card">
        <div class="header-container">
            <a href="menu.php" class="back-btn-icon" title="Voltar ao Menu">&larr;</a>
            <h2>Ocorrências em Andamento</h2>
            <a href="menu.php" class="close-btn" title="Voltar ao Menu">&times;</a>
        </div>

        <div class="action-buttons">
            <button id="btnManutencoes" class="action-btn active" data-type="manutencao">Manutenções</button>
            <button id="btnInstalacoes" class="action-btn" data-type="instalação">Instalações</button>
        </div>

        <?php if (!empty($cidades_com_ocorrencias)): ?>
        <div class="filter-container">
            <button class="filter-btn active" data-city="todos">Todos</button>
            <?php foreach ($cidades_com_ocorrencias as $cidade): ?>
                <button class="filter-btn" data-city="<?php echo htmlspecialchars($cidade); ?>"><?php echo htmlspecialchars($cidade); ?></button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="ocorrencias-container">
            <?php if (!empty($errorMessage)): ?>
                <p><?php echo $errorMessage; ?></p>
            <?php else: ?>
                <?php foreach ($ocorrencias_por_cidade as $cidade => $ocorrencias_na_cidade): ?>
                    <div class="city-group" data-city="<?php echo htmlspecialchars($cidade); ?>">
                        <h2 class="city-group-title"><?php echo htmlspecialchars($cidade); ?></h2>
                        <div class="city-ocorrencias-grid">
                            <?php foreach ($ocorrencias_na_cidade as $item): ?>
                                <?php
                                    // --- Lógica para calcular o tempo de reparo/instalação ---
                                    $tempoReparo = "N/A";
                                    if (!empty($item['inicio_periodo_reparo']) && !empty($item['fim_periodo_reparo'])) {
                                        $inicio = new DateTime($item['inicio_periodo_reparo']);
                                        $fim = new DateTime($item['fim_periodo_reparo']);
                                        $diff = $inicio->diff($fim);
                                        $tempoReparo = $inicio->format('d/m/Y') . ' até ' . $fim->format('d/m/Y') . ' (' . ($diff->days + 1) . ' dia(s))';
                                    }
                                    $tipoOcorrencia = $item['tipo_manutencao'];
                                    // Acentuação para exibição
                                    if ($tipoOcorrencia == 'instalação') $tipoOcorrencia = 'Instalação';
                                ?>
                                <div class="ocorrencia-item" data-type="<?php echo htmlspecialchars($item['tipo_manutencao']); ?>">
                                    <div class="ocorrencia-header">
                                        <h3><?php echo htmlspecialchars($item['nome_equip'] . ' - ' . $item['referencia_equip']); ?></h3>
                                        <!-- A cidade já está no título do grupo, então podemos omitir aqui se quisermos -->
                                    </div>

                                    <div class="ocorrencia-details">
                                        <?php if ($item['tipo_manutencao'] != 'instalação'): ?>
                                            <!-- Layout de Manutenção -->
                                            <div class="detail-item">
                                                <strong>Ocorrência</strong> <span><?php echo htmlspecialchars($item['ocorrencia_reparo']); ?></span>
                                            </div>
                                            <div class="detail-item">
                                                <strong>Técnico(s)</strong> <span><?php echo htmlspecialchars($item['tecnicos_nomes'] ?: 'Não atribuído'); ?></span>
                                            </div>
                                            <div class="detail-item">
                                                <strong>Início Ocorrência</strong> <span><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($item['inicio_reparo']))); ?></span>
                                            </div>
                                            <div class="detail-item">
                                                <strong>Status</strong> <span class="status-em-andamento">Em andamento</span>
                                            </div>
                                            <div class="detail-item">
                                                <strong>Local</strong> <span><?php echo htmlspecialchars($item['local_completo']); ?></span>
                                            </div>
                                            
                                            <div class="detail-item">
                                                <strong>Tempo Reparo</strong> <span><?php echo htmlspecialchars($tempoReparo); ?></span>
                                            </div>
                                        <?php else: ?>
                                            <!-- Layout de Instalação -->
                                            <div class="detail-item stacked">
                                                <strong>Tipo</strong>
                                                <span><?php echo htmlspecialchars(ucfirst($tipoOcorrencia)); ?></span>
                                            </div>
                                            <div class="detail-item">
                                                <strong>Laço</strong>
                                                <span>
                                                    <?php if ($item['inst_laco']): ?>
                                                        <span class="status-value instalado">Instalado <?php echo date('d/m/Y', strtotime($item['dt_laco'])); ?></span>
                                                    <?php else: ?>
                                                        <span class="status-value aguardando">Aguardando instalação</span>
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                            <div class="detail-item">
                                                <strong>Base</strong>
                                                <span>
                                                    <?php if ($item['inst_base']): ?>
                                                        <span class="status-value instalado">Instalado <?php echo date('d/m/Y', strtotime($item['dt_base'])); ?></span>
                                                    <?php else: ?>
                                                        <span class="status-value aguardando">Aguardando instalação</span>
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                            <div class="detail-item">
                                                <strong>Infra</strong>
                                                <span>
                                                    <?php if ($item['inst_infra']): ?>
                                                        <span class="status-value instalado">Instalado <?php echo date('d/m/Y', strtotime($item['data_infra'])); ?></span>
                                                    <?php else: ?>
                                                        <span class="status-value aguardando">Aguardando instalação</span>
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                            <div class="detail-item">
                                                <strong>Energia</strong>
                                                <span>
                                                    <?php if ($item['inst_energia']): ?>
                                                        <span class="status-value instalado">Instalado <?php echo date('d/m/Y', strtotime($item['dt_energia'])); ?></span>
                                                    <?php else: ?>
                                                        <span class="status-value aguardando">Aguardando instalação</span>
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                            <div class="detail-item">
                                                <strong>Local</strong> <span><?php echo htmlspecialchars($item['local_completo']); ?></span>
                                            </div>
                                            <div class="detail-item">
                                                <strong>Início Ocorrência</strong> <span><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($item['inicio_reparo']))); ?></span>
                                            </div>
                                            <div class="detail-item">
                                                <strong>Técnico(s)</strong> <span><?php echo htmlspecialchars($item['tecnicos_nomes'] ?: 'Não atribuído'); ?></span>
                                            </div>
                                            <div class="detail-item">
                                                <strong>Tempo Instalação</strong> <span><?php echo htmlspecialchars($tempoReparo); ?></span>
                                            </div>
                                            <div class="detail-item">
                                                <strong>Status</strong> <span class="status-em-andamento">Em andamento</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <a href="menu.php" class="voltar-btn">Voltar ao Menu</a>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const actionButtons = document.querySelectorAll('.action-btn');
            const filterButtons = document.querySelectorAll('.filter-btn');
            const cityGroups = document.querySelectorAll('.city-group');

            let activeType = 'manutencao';
            let activeCity = 'todos';

            function updateDisplay() {
                cityGroups.forEach(group => {
                    const groupCity = group.dataset.city;
                    let hasVisibleItemsInGroup = false;

                    const cityMatch = activeCity === 'todos' || groupCity === activeCity;

                    if (cityMatch) {
                        group.querySelectorAll('.ocorrencia-item').forEach(item => {
                            const itemType = item.dataset.type;
                            let typeMatch = false;

                            if (activeType === 'manutencao') {
                                if (['corretiva', 'preventiva', 'preditiva'].includes(itemType)) {
                                    typeMatch = true;
                                }
                            } else if (activeType === 'instalação') {
                                if (itemType === 'instalação') {
                                    typeMatch = true;
                                }
                            }

                            if (typeMatch) {
                                item.classList.remove('hidden');
                                hasVisibleItemsInGroup = true;
                            } else {
                                item.classList.add('hidden');
                            }
                        });

                        if (hasVisibleItemsInGroup) {
                            group.classList.remove('hidden');
                        } else {
                            group.classList.add('hidden');
                        }
                    } else {
                        group.classList.add('hidden');
                    }
                });
            }

            actionButtons.forEach(button => {
                button.addEventListener('click', () => {
                    actionButtons.forEach(btn => btn.classList.remove('active'));
                    button.classList.add('active');
                    activeType = button.dataset.type;
                    updateDisplay();
                });
            });

            filterButtons.forEach(button => {
                button.addEventListener('click', () => {
                    filterButtons.forEach(btn => btn.classList.remove('active'));
                    button.classList.add('active');
                    activeCity = button.dataset.city;
                    updateDisplay();
                });
            });

            updateDisplay();
        });
    </script>
</body>
</html>
