<?php
date_default_timezone_set('America/Sao_Paulo');
header('Content-Type: application/json');

$uri = "mongodb+srv://Monitoramento:123@cluster0.mg3nr90.mongodb.net/?appName=Cluster0";

try {
    $manager = new MongoDB\Driver\Manager($uri);
    $query = new MongoDB\Driver\Query([], ['sort' => ['data_hora' => -1], 'limit' => 60]);
    $cursor = $manager->executeQuery('db_infra.historico_hw', $query);
    
    $resultados = [];
    foreach ($cursor as $doc) {
        $resultados[] = $doc;
    }
    echo json_encode($resultados);
} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>
