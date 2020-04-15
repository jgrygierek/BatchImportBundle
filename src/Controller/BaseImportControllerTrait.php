<?php

namespace JG\BatchImportBundle\Controller;

use InvalidArgumentException;
use JG\BatchImportBundle\Form\Type\FileImportType;
use JG\BatchImportBundle\Model\Configuration\ImportConfigurationInterface;
use JG\BatchImportBundle\Model\FileImport;
use JG\BatchImportBundle\Model\Matrix\Matrix;
use JG\BatchImportBundle\Model\Matrix\MatrixFactory;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderException;
use Symfony\Component\Form\Exception\LogicException;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use UnexpectedValueException;

trait BaseImportControllerTrait
{
    private ?ImportConfigurationInterface $importConfiguration = null;

    /**
     * @param Request $request
     *
     * @return Response
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws ReaderException
     * @throws \LogicException
     */
    private function doImport(Request $request): Response
    {
        $fileImport = new FileImport();

        /** @var FormInterface $form */
        $form = $this->createForm(FileImportType::class, $fileImport);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $matrix = MatrixFactory::createFromUploadedFile($fileImport->getFile());

            return $this->prepareMatrixEditView($matrix);
        }

        $this->addFormErrorToFlash($form);

        return $this->prepareSelectFileView($form);
    }

    private function prepareSelectFileView(FormInterface $form): Response
    {
        return $this->prepareView(
            $this->getSelectFileTemplateName(),
            [
                'form' => $form->createView(),
            ]
        );
    }

    private function prepareMatrixEditView(Matrix $matrix): Response
    {
        return $this->prepareView(
            $this->getMatrixEditTemplateName(),
            [
                'header' => $matrix->getHeader(),
                'data'   => $matrix->getRecords(),
                'form'   => $this->createMatrixForm($matrix)->createView(),
            ]
        );
    }

    /**
     * @param Request $request
     *
     * @return Response
     * @throws LogicException
     */
    private function doImportSave(Request $request): Response
    {
        if (!isset($request->get('matrix')['records'])) {
            //todo: add translation
            $this->addFlash('error', 'No data found');

            return $this->redirectToImport();
        }

        $matrix = MatrixFactory::createFromPostData($request->get('matrix')['records']);
        $form   = $this->createMatrixForm($matrix);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $config = $this->getImportConfiguration();
            foreach ($matrix->getRecords() as $record) {
                $config->prepareRecord($record);
            }

            $config->save();
            //todo: add translation
            $this->addFlash('success', 'Data has been imported successfully.');
        }

        $this->addFormErrorToFlash($form);

        return $this->redirectToImport();
    }

    private function getImportConfiguration(): ImportConfigurationInterface
    {
        if (!$this->importConfiguration) {
            $class = $this->getImportConfigurationClassName();
            if (!class_exists($class)) {
                throw new UnexpectedValueException('Configuration class not found.');
            }

            $this->importConfiguration = new $class($this->get('doctrine')->getManager());
        }

        return $this->importConfiguration;
    }

    private function addFormErrorToFlash(FormInterface $form): void
    {
        $errors = iterator_to_array($form->getErrors());
        if ($errors) {
            $error = reset($errors);
            $this->addFlash('error', $error->getMessage());
        }
    }
}
