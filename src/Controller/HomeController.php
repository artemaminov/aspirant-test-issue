<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Movie;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpBadRequestException;
use Slim\Interfaces\RouteCollectorInterface;
use Twig\Environment;
use App\Command\FetchDataCommand;
use Feed;

/**
 * Class HomeController.
 */
class HomeController
{
    /**
     * @var RouteCollectorInterface
     */
    private $routeCollector;

    /**
     * @var Environment
     */
    private $twig;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * HomeController constructor.
     *
     * @param RouteCollectorInterface $routeCollector
     * @param Environment             $twig
     * @param EntityManagerInterface  $em
     */
    public function __construct(RouteCollectorInterface $routeCollector, Environment $twig, EntityManagerInterface $em)
    {
        $this->routeCollector = $routeCollector;
        $this->twig = $twig;
        $this->em = $em;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     *
     * @return ResponseInterface
     *
     * @throws HttpBadRequestException
     */
    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            if ($request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest') {
                $trailers = $this->updateTrailersData();
                $data = $this->twig->render('home/feedlist.json.twig', [
                    'trailers' => $trailers,
                ]);
                $response->getBody()->write($data);
                return $response->withHeader('Content-Type', 'application/json');
            } else {
                $data = $this->twig->render('home/index.html.twig', [
                    'trailers' => $this->fetchData(),
                    'datestring' => $this->getTime(),
                    'systeminfo' => [
                        'class' => __CLASS__,
                        'method' => __FUNCTION__
                    ],
                ]);
                $response->getBody()->write($data);
                return $response;
            }
        } catch (\Exception $e) {
            throw new HttpBadRequestException($request, $e->getMessage(), $e);
        }
    }

    public function updateTrailersData(): Collection
    {
        $rss = Feed::loadRss(FetchDataCommand::SOURCE);
        foreach ($rss->item as $item) {
            $trailer = $this->getMovie((string) $item->title)
                ->setTitle((string) $item->title)
                ->setDescription((string) $item->description)
                ->setLink((string) $item->link)
                ->setPubDate($this->parseDate((string) $item->pubDate))
            ;
            $this->em->persist($trailer);
        }
        $this->em->flush();
        return $this->fetchData();
    }

    protected function getMovie(string $title): Movie
    {
        $item = $this->em->getRepository(Movie::class)->findOneBy(['title' => $title]);
        if ($item === null) {
            $item = new Movie();
        }
        if (!($item instanceof Movie)) {
            throw new RuntimeException('Wrong type!');
        }
        return $item;
    }

    /**
     * @return Collection
     */
    protected function fetchData(): Collection
    {
        $data = $this->em->getRepository(Movie::class)->findAll();
        return new ArrayCollection($data);
    }

    protected function getTime()
    {
        $datestring = date('D, j').' of '.date('F Y').' | '.date('G:i');
        return $datestring;
    }

    /**
     * @param string $date
     *
     * @return \DateTime
     *
     * @throws \Exception
     */
    protected function parseDate(string $date): \DateTime
    {
        return new \DateTime($date);
    }
}
