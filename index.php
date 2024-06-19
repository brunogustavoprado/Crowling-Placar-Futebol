<?php
require 'vendor/autoload.php';

use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;
use GuzzleHttp\Exception\RequestException;

$client = new Client([
    'base_uri' => 'https://futebolaovivo.com.br/jogos-ao-vivo/',
    'timeout'  => 10.0,
    'verify'   => false,
]);

function fetchMatchData($client, $url) {
    try {
        $response = $client->request('GET', $url);

        if ($response->getStatusCode() === 200) {
            $htmlContent = $response->getBody()->getContents();

            $crawler = new Crawler($htmlContent);

            $games = $crawler->filter('.col-times');

            $matchData = [];

            $games->each(function (Crawler $node, $i) use (&$matchData, $crawler) {
                $homeTeamNode = $node->filter('.logo-time-casa span');
                $homeTeamNodeIMG = $node->filter('.logo-time-casa img');
                $awayTeamNode = $node->filter('.logo-time-visitante span');
                $awayTeamNodeIMG = $node->filter('.logo-time-visitante img');

                if ($homeTeamNode->count() > 0 && $awayTeamNode->count() > 0) {
                    $homeTeam = $homeTeamNode->text();
                    $awayTeam = $awayTeamNode->text();
                } else {
                    throw new Exception('Elementos de times não encontrados.');
                }

                $homeTeamImg = $homeTeamNodeIMG->attr('src');
                $awayTeamImg = $awayTeamNodeIMG->attr('src');

                if (empty($homeTeamImg) || empty($awayTeamImg)) {
                    throw new Exception('URLs das imagens não encontrados.');
                }

                $scoreNode = $crawler->filter('.col-placar')->eq($i);
                $homeScoreNode = $scoreNode->filter('.placar-casa');
                $awayScoreNode = $scoreNode->filter('.placar-visitante');

                if ($homeScoreNode->count() > 0 && $awayScoreNode->count() > 0) {
                    $homeScore = $homeScoreNode->text();
                    $awayScore = $awayScoreNode->text();
                } else {
                    throw new Exception('Elementos de placar não encontrados.');
                }

                $dateTimeNode = $crawler->filter('.partida-tempo')->eq($i);
                $dateTime = $dateTimeNode->text();

                $matchData[] = [
                    'date_time' => trim($dateTime),
                    'home_team' => trim($homeTeam),
                    'home_team_img' => $homeTeamImg,
                    'away_team' => trim($awayTeam),
                    'away_team_img' => $awayTeamImg,
                    'home_score' => trim($homeScore),
                    'away_score' => trim($awayScore),
                ];
            });

            return $matchData;
        } else {
            throw new Exception('Erro ao acessar a página.');
        }
    } catch (RequestException $e) {
        echo 'Erro ao fazer a requisição: ' . $e->getMessage();
    } catch (Exception $e) {
        echo 'Erro: ' . $e->getMessage();
    }

    return [];
}

$url = 'https://futebolaovivo.com.br/jogos-ao-vivo/';

$matchesData = fetchMatchData($client, $url);

// Retorna os dados como JSON
header('Content-Type: application/json');
echo json_encode($matchesData);
?>
