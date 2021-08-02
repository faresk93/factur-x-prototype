<?php

namespace App\Controller;

use Atgp\FacturX\Facturx;
use DOMDocument;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController
{
    /**
     * @Route("/", name="index")
     */
    public function index(): Response
    {
        return $this->render('index/index.html.twig', [
            'controller_name' => 'IndexController'
        ]);
    }

    /**
     * @Route("/get-factur-x/{type}", name="get-factur-x")
     */
    public function downloadFacturX(string $type, KernelInterface $kernel)
    {
        $facturxPdf = $kernel->getProjectDir() . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'test-facture-x.pdf';
        $whateverPdf = $kernel->getProjectDir() . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'test-whatever.pdf';
        $facturxXml = $kernel->getProjectDir() . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'test-facture-x.xml';
        switch ($type) {
            case 'pdf':
                $file = $whateverPdf;
                break;
            case 'pdf-x':
                $file = $facturxPdf;
                break;
            case 'xml':
                $file = $facturxXml;
                break;
        }
        if (!isset($file)) {
            throw new FileNotFoundException();
        }
        return $this->file($file);
    }

    /**
     * @Route("/extract-factur-x-xml", name="extract-factur-x-xml")
     *
     * @param KernelInterface $kernel
     * @return BinaryFileResponse
     * @throws Exception
     */
    public function extractXMLFromFacturX(KernelInterface $kernel)
    {
        $facturxPdf = $kernel->getProjectDir() . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'test-facture-x.pdf';

        // Extract Factur-X XML from Factur-X PDF invoice
        $facturx = new Facturx();
        $xmlData = $facturx->getFacturxXmlFromPdf($facturxPdf);
        $extractedXML = $kernel->getProjectDir() . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'extracted-factur-x-xml.xml';
        file_put_contents($extractedXML, $xmlData);

        return $this->file($extractedXML)->deleteFileAfterSend();
    }

    /**
     * @Route("validate-xml", name="validate-xml")
     *
     * @param KernelInterface $kernel
     * @return JsonResponse
     * @throws Exception
     */
    public function validateXML(KernelInterface $kernel)
    {
        $facturxXML = $kernel->getProjectDir() . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'test-facture-x.xml';

        // Check Factur-X XML against the official Factur-X XML Schema Definition
        $facturx = new Facturx();
        try {
            $isValid = $facturx->checkFacturxXsd(file_get_contents($facturxXML));
            $message = 'success';
        } catch (Exception $e) {
            $isValid = false;
            $message = $e->getMessage();
        }


        return new JsonResponse(['valid' => $isValid, 'message' => $message], 200);
    }

    /**
     * @Route("get-xml-profile", name="get-xml-profile")
     *
     * @param KernelInterface $kernel
     * @return JsonResponse
     * @throws Exception
     */
    public function getXMLProfile(KernelInterface $kernel)
    {
        $facturxXML = $kernel->getProjectDir() . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'test-facture-x.xml';

        // Check Factur-X XML against the official Factur-X XML Schema Definition
        $facturx = new Facturx();
        $facturxXMLDocument = new DOMDocument();
        $facturxXMLDocument->loadXML(file_get_contents($facturxXML));
        $profil = $facturx->getFacturxProfil($facturxXMLDocument);

        return new JsonResponse($profil, 200);

    }

    /**
     * @Route("attach-xml", name="attach-xml")
     *
     * @param KernelInterface $kernel
     */
    public function attachXMLToPdf(KernelInterface $kernel)
    {
        $whateverPdf = $kernel->getProjectDir() . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'test-whatever.pdf';
        $facturxXML = $kernel->getProjectDir() . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'test-facture-x.xml';
        $xmlData = file_get_contents($facturxXML);
        $path = $kernel->getProjectDir() . DIRECTORY_SEPARATOR . 'public';

        // Check Factur-X XML against the official Factur-X XML Schema Definition
        $facturx = new Facturx();
        $facturxPdf = $facturx->generateFacturxFromFiles($whateverPdf, $xmlData, 'autodetect', true, $path, [], true);

        return $this->file($facturxPdf)->deleteFileAfterSend();
    }

    // TODO
    public function createXML()
    {
        // TODO complete Data
        $data = [
            'ID' => 'FA-2017-0009',
            'TypeCode' => 380,
            'IssueDateTime' => '20171105',
            'SellerTradeParty' => [
                'Name' => 'Au bon moulin',
                'SpecifiedLegalOrganization' => [
                    'schemeID' => '0002',
                    'ID' => '34343434600010'
                ]
            ]
        ];

        // TODO create Factur-X XML

        // TODO handle attachment to PDF
    }
}
