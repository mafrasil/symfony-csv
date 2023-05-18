<?php

namespace App\Controller;

use App\Entity\Url;
use App\Repository\UrlRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\HttpFoundation\Request;

class MainController extends AbstractController
{
    #[Route('/', name: 'csv_importer')]
    public function importer(Request $request, EntityManagerInterface $em, UrlRepository $urlRepository): Response
    {
        $urls = $em->getRepository(Url::class)->findAll();

        $form = $this->createFormBuilder()
                    ->add('csv', FileType::class)
                    ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            $csvFile = $form->get('csv')->getData();
            $file = fopen($csvFile, 'r');

            $i = 0;
            $hashesInBatch = [];
            while (($line = fgetcsv($file)) !== FALSE) {
                $url = new Url();
                $normalizedUrl = $url->normalizeUrl($line[0]);
                $hash = hash('sha256', $normalizedUrl);
                $existingUrl = $urlRepository->findOneByUrlHash($hash);                
                if ($existingUrl === null && !isset($hashesInBatch[$hash])) {
                    $url = new Url();
                    $url->setUrl($normalizedUrl);
                    $url->setUrlHash($hash);
                    $em->persist($url);
                    $hashesInBatch[$hash] = true;
                    if (($i % 20) === 0) {
                        $em->flush();
                        $em->clear();
                    }
                    $i++;
                }
            }

            $em->flush();
            $this->addFlash('success', "$i new URLs have been added.");
            
            return $this->redirectToRoute('csv_importer');
        }

        return $this->render('form.html.twig', [
            'form' => $form->createView(),
            'urls' => $urls,
        ]);
    }
}
