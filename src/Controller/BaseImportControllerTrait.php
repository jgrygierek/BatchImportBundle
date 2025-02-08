<?php

declare(strict_types=1);

namespace JG\BatchEntityImportBundle\Controller;

use InvalidArgumentException;
use JG\BatchEntityImportBundle\Exception\BatchEntityImportExceptionInterface;
use JG\BatchEntityImportBundle\Form\Type\FileImportType;
use JG\BatchEntityImportBundle\Model\Configuration\ImportConfigurationInterface;
use JG\BatchEntityImportBundle\Model\FileImport;
use JG\BatchEntityImportBundle\Model\Matrix\Matrix;
use JG\BatchEntityImportBundle\Model\Matrix\MatrixFactory;
use JG\BatchEntityImportBundle\Model\Matrix\MatrixRecord;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\Form\Exception\LogicException;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Traversable;

trait BaseImportControllerTrait
{
    protected ?ImportConfigurationInterface $importConfiguration = null;

    abstract protected function getImportConfigurationClassName(): string;

    abstract protected function redirectToImport(): RedirectResponse;

    abstract protected function getSelectFileTemplateName(): string;

    abstract protected function getMatrixEditTemplateName(): string;

    abstract protected function prepareView(string $view, array $parameters = []): Response;

    abstract protected function createMatrixForm(Matrix $matrix): FormInterface;

    /**
     * @throws InvalidArgumentException
     * @throws \LogicException
     */
    protected function doImport(Request $request, ValidatorInterface $validator): Response
    {
        $fileImport = new FileImport($this->getImportConfiguration()->getAllowedFileExtensions());

        $form = $this->createForm(FileImportType::class, $fileImport);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $matrix = MatrixFactory::createFromUploadedFile($fileImport->getFile());

            $errors = $validator->validate($matrix);
            if (0 === $errors->count()) {
                $form = $this->createMatrixForm($matrix);

                return $this->prepareMatrixEditView($form, $matrix, true);
            }
        } else {
            $errors = $form->getErrors();
        }

        $this->setErrorAsFlash($errors);

        return $this->prepareSelectFileView($form);
    }

    protected function prepareSelectFileView(FormInterface $form): Response
    {
        return $this->prepareView(
            $this->getSelectFileTemplateName(),
            [
                'form' => $form->createView(),
            ]
        );
    }

    protected function prepareMatrixEditView(FormInterface $form, Matrix $matrix, bool $manualSubmit = false): Response
    {
        if ($manualSubmit) {
            $this->manualSubmitMatrixForm($form, $matrix);
        }

        $configuration = $this->getImportConfiguration();

        return $this->prepareView(
            $this->getMatrixEditTemplateName(),
            [
                'header_info' => $matrix->getHeaderInfo($configuration->getEntityClassName()),
                'form' => $form->createView(),
                'importConfiguration' => $configuration,
            ]
        );
    }

    /**
     * @throws LogicException
     */
    protected function doImportSave(Request $request, TranslatorInterface $translator): Response
    {
        if (!isset($request->get('matrix')['records'])) {
            $msg = $translator->trans('error.data.not_found', [], 'BatchEntityImportBundle');
            $this->addFlash('error', $msg);

            return $this->redirectToImport();
        }

        $matrix = MatrixFactory::createFromPostData($request->get('matrix')['records']);
        $form = $this->createMatrixForm($matrix);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->getImportConfiguration()->import($matrix);
                $msg = $translator->trans('success.import', [], 'BatchEntityImportBundle');
                $this->addFlash('success', $msg);

                return $this->redirectToImport();
            } catch (BatchEntityImportExceptionInterface $e) {
                $msg = $translator->trans($e->getMessage(), [], 'BatchEntityImportBundle');
                $this->addFlash('error', $msg);
            }
        }

        $this->setErrorAsFlash($form->getErrors());

        return $this->prepareMatrixEditView($form, $matrix);
    }

    protected function getImportConfiguration(): ImportConfigurationInterface
    {
        if (!$this->importConfiguration) {
            throw new ServiceNotFoundException($this->getImportConfigurationClassName());
        }

        return $this->importConfiguration;
    }

    protected function setErrorAsFlash(Traversable $violations): void
    {
        $errors = iterator_to_array($violations);
        if ($errors) {
            $error = reset($errors);
            $this->addFlash('error', $error->getMessage());
        }
    }

    protected function manualSubmitMatrixForm(FormInterface $form, Matrix $matrix): void
    {
        $data = ['records' => array_map(static fn (MatrixRecord $record): array => $record->getData(), $matrix->getRecords())];

        $csrfTokenManager = $form->getConfig()->getOption('csrf_token_manager');
        if ($csrfTokenManager) {
            $tokenId = $form->getConfig()->getOption('csrf_token_id') ?? $form->getName();
            $data['_token'] = $csrfTokenManager->getToken($tokenId)->getValue();
        }

        $form->submit($data);
    }
}
