<?php
date_default_timezone_set('America/Sao_Paulo');

$uri = "mongodb+srv://Monitoramento:123@cluster0.mg3nr90.mongodb.net/?appName=Cluster0";

try {
    $manager = new MongoDB\Driver\Manager($uri);
    $query = new MongoDB\Driver\Query([], ['sort' => ['data_hora' => -1], 'limit' => 1]);
    $cursor = $manager->executeQuery('db_infra.historico_hw', $query);

} catch (Exception $e) {
    die("Erro ao conectar no MongoDB: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>NOC - Monitoramento Inteligente</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #121212; color: white; padding: 20px; }
        h1 { text-align: center; color: #007bff; }
        #dashboard-container { display: flex; flex-wrap: wrap; gap: 20px; justify-content: center; align-items: flex-start; }
        
        /* Card um pouco mais largo para caber as 4 métricas */
        .card-servidor {
            background: #1e1e1e; border: 2px solid #333; border-radius: 10px;
            width: 250px; padding: 15px; 
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .card-servidor:hover { border-color: #0db7ff; }
        .card-servidor h3 { margin-top: 0; color: #0db7ff; border-bottom: 1px solid #333; padding-bottom: 10px; text-align: center; }
        .metricas-grid { display: flex; flex-direction: column; gap: 5px; margin-bottom: 15px; }
        .metric { font-size: 1.05em; display: flex; justify-content: space-between; }
        .time-val { font-size: 0.8em; color: #888; text-align: center; margin-top: 10px;}
        
        /* Alerta Crítico */
        .critico { 
            border-color: #ff4d4d !important; 
            background: #311 !important; 
            box-shadow: 0 0 15px #f00; 
            animation: pulse-red 2s infinite;
        }
        @keyframes pulse-red { 0% { box-shadow: 0 0 10px #f00; } 50% { box-shadow: 0 0 20px #f00; } 100% { box-shadow: 0 0 10px #f00; } }
        
        .grafico-container { 
            width: 100%; height: 0px; 
            opacity: 0; overflow: hidden;
            transition: all 0.3s ease;
        }

        /* Card Expandido mais largo para os 4 itens ficarem bonitos */
        .card-servidor.expandido { width: 480px; }
        .card-servidor.expandido .metricas-grid { flex-direction: row; flex-wrap: wrap; justify-content: space-around; gap: 15px; }
        .card-servidor.expandido .metric { flex-direction: column; align-items: center; }
        .card-servidor.expandido .grafico-container { height: 160px; opacity: 1; margin-top: 15px; }
    </style>
</head>
<body>
    <h1>Painel de Controle de Infraestrutura (NOC)</h1>
    <div id="status" style="text-align:center; margin-bottom:20px; color:#888;">Estabelecendo conexão...</div>
    <div id="dashboard-container"></div>

    <script>
        const graficosAtivos = {};
        const cardsExpandidos = new Set(); 

        function atualizarDashboard() {
            fetch('dados.php')
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('dashboard-container');
                    const historicoPorServidor = {};
                    
                    data.forEach(item => {
                        if (!historicoPorServidor[item.servidor]) {
                            historicoPorServidor[item.servidor] = [];
                        }
                        historicoPorServidor[item.servidor].push(item);
                    });

                    for (const servidor in historicoPorServidor) {
                        const registros = historicoPorServidor[servidor];
                        const dadosAtuais = registros[0]; 

                        registros.reverse(); 
                        const labelsHora = registros.map(r => r.data_hora.split(' ')[1]); 
                        const historicoCpu = registros.map(r => parseFloat(r.cpu));
                        const historicoArmazenamento = registros.map(r => parseFloat(r.armazenamento)); 

                        let card = document.getElementById(`card-${servidor}`);
                        
                        if (!card) {
                            card = document.createElement('div');
                            card.id = `card-${servidor}`;
                            card.className = 'card-servidor';
                            
                            // Apenas o clique do usuário controla o abrir/fechar
                            card.onclick = function() {
                                if (cardsExpandidos.has(servidor)) {
                                    cardsExpandidos.delete(servidor);
                                    card.classList.remove('expandido');
                                } else {
                                    cardsExpandidos.add(servidor);
                                    card.classList.add('expandido');
                                }
                            };

                            // HTML atualizado com 4 métricas
                            card.innerHTML = `
                                <h3>${servidor}</h3>
                                <div class="metricas-grid">
                                    <div class="metric"><span>CPU:</span> <strong class="val-cpu">0%</strong></div>
                                    <div class="metric"><span>RAM:</span> <strong class="val-ram">0%</strong></div>
                                    <div class="metric"><span>I/O Disco:</span> <strong class="val-io">0 MB/s</strong></div>
                                    <div class="metric"><span>Armaz.:</span> <strong class="val-arm">0%</strong></div>
                                </div>
                                <div class="grafico-container">
                                    <canvas id="canvas-${servidor}"></canvas>
                                </div>
                                <div class="time-val val-time">--</div>
                            `;
                            container.appendChild(card);
                        }

                        // LÓGICA DE ALERTA: Apenas muda a cor, não força a abertura!
                        const isCritico = (dadosAtuais.cpu > 90 || dadosAtuais.disco > 90);

                        if (isCritico) {
                            card.classList.add('critico');
                        } else {
                            card.classList.remove('critico');
                        }

                        // Aplica o estado de expandido apenas se o usuário tiver clicado
                        if (cardsExpandidos.has(servidor)) {
                            card.classList.add('expandido');
                        } else {
                            card.classList.remove('expandido');
                        }

                        // Atualiza os valores em tempo real
                        card.querySelector('.val-cpu').innerText = parseFloat(dadosAtuais.cpu).toFixed(2) + '%';
                        card.querySelector('.val-ram').innerText = parseFloat(dadosAtuais.ram).toFixed(2) + '%';
                        card.querySelector('.val-io').innerText = parseFloat(dadosAtuais.disco).toFixed(3) + ' MB/s';
                        card.querySelector('.val-arm').innerText = parseFloat(dadosAtuais.armazenamento).toFixed(1) + '%';
                        card.querySelector('.val-time').innerText = "Atualizado: " + dadosAtuais.data_hora;

                        // Atualiza o Gráfico Duplo
                        const ctx = document.getElementById(`canvas-${servidor}`).getContext('2d');
                        if (!graficosAtivos[servidor]) {
                            graficosAtivos[servidor] = new Chart(ctx, {
                                type: 'line',
                                data: {
                                    labels: labelsHora,
                                    datasets: [
                                        {
                                            label: 'CPU (%)',
                                            data: historicoCpu,
                                            borderColor: '#0db7ff',
                                            backgroundColor: 'rgba(13, 183, 255, 0.1)',
                                            borderWidth: 2, fill: true, tension: 0.3, pointRadius: 0
                                        },
                                        {
                                            label: 'Armazenamento (%)',
                                            data: historicoArmazenamento,
                                            borderColor: '#ff9900',
                                            backgroundColor: 'rgba(255, 153, 0, 0.1)',
                                            borderWidth: 2, fill: true, tension: 0.3, pointRadius: 0
                                        }
                                    ]
                                },
                                options: {
                                    responsive: true, maintainAspectRatio: false, animation: false,
                                    scales: { y: { min: 0, max: 100 } },
                                    plugins: { legend: { display: false }, tooltip: { enabled: false } }
                                }
                            });
                        } else {
                            graficosAtivos[servidor].data.labels = labelsHora;
                            graficosAtivos[servidor].data.datasets[0].data = historicoCpu;
                            graficosAtivos[servidor].data.datasets[1].data = historicoArmazenamento;
                            graficosAtivos[servidor].update();
                        }
                    }

                    document.getElementById('status').innerText = "Última sincronização: " + new Date().toLocaleTimeString();
                })
                .catch(error => console.error('Erro:', error));
        }

        setInterval(atualizarDashboard, 1000);
        atualizarDashboard();
    </script>
</body>
</html>