<?php

namespace Drupal\muser_system\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\File\FileSystem;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use setasign\FPDF\FPDF;
use setasign\Fpdi\Fpdi;
use Drupal\views\Views;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Settings as PhpWordSettings;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Returns responses for Muser System routes.
 */
class MuserPdfExportController extends ControllerBase {

  /**
   * Encoding value to convert to.
   */
  const ENCODING = 'CP1251';

  /**
   * @var Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The FileSystem service.
   *
   * @var \Drupal\Core\File\FileSystem
   */
  protected $fileSystem;

  /**
   * Logger Factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The controller constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity type manager.
   * @param \Drupal\Core\File\FileSystem $file_system
   *   Filesystem service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   Logger Factory object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AccountInterface $current_user, EntityTypeManagerInterface $entity_manager, FileSystem $file_system, LoggerChannelFactoryInterface $logger_factory) {
    $this->configFactory = $config_factory;
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_manager;
    $this->fileSystem = $file_system;
    $this->loggerFactory = $logger_factory->get('muser_system');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('file_system'),
      $container->get('logger.factory')
    );
  }

  /**
   * Create a PDF of the applications for the current user..
   *
   * Documentation:
   * http://www.fpdf.org/en/doc/index.php
   *
   * @param string $stage
   *   Stage to show applications for (submitted|review).
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Build array.
   * @throws \setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException
   * @throws \setasign\Fpdi\PdfParser\Filter\FilterException
   * @throws \setasign\Fpdi\PdfParser\PdfParserException
   * @throws \setasign\Fpdi\PdfParser\Type\PdfTypeException
   * @throws \setasign\Fpdi\PdfReader\PdfReaderException
   */
  public function build($stage) {

    $display = ($stage == 'in-review') ? 'page_review' : 'page_new';

    $args = [$this->currentUser->id()];
    $view = Views::getView('applications');
    if (is_object($view)) {
      $view->setArguments($args);
      $view->setDisplay($display);
      $view->preExecute();
      $view->execute();
    }

    if (empty($view->result)) {
      throw new NotFoundHttpException();
    }

    $flagging_handler = $this->entityTypeManager->getStorage('flagging');
    $node_handler = $this->entityTypeManager->getStorage('node');
    $user_handler = $this->entityTypeManager->getStorage('user');

    $pdf = new Fpdi();

    foreach ($view->result as $row) {

      /** @var \Drupal\flag\FlaggingInterface $flagging */
      $flagging = $flagging_handler->load($row->flagging_node_field_data_id);
      $project = $node_handler->load($row->node_field_data_node__field_project_nid);
      $applicant = $user_handler->load($flagging->getOwnerId());

      $pdf->AddPage();
      $pdf->SetFont('Arial', 'B', 16);
      $pdf->Cell(40, 10, $this->convertEncoding($project->label()));
      $pdf->Ln();

      if ($display == 'page_review') {

        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(40, 10, $this->t('Applicant information'));
        $pdf->Ln();

        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(20, 10, $this->t('Name:'));
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(80, 10, $this->convertEncoding($applicant->getDisplayName()));
        $pdf->Ln();

        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(20, 10, $this->t('Email:'));
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(80, 10, $this->convertEncoding($applicant->getEmail()));
        $pdf->Ln();

        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(40, 10, $this->t('About me:'));
        if ($text = $applicant->field_about_me->value) {
          $pdf->Ln();
          $this->addText($pdf, $text, TRUE);
        }
        $pdf->Ln();

        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(40, 10, $this->t('Student information'));
        $pdf->Ln();

        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(40, 10, $this->t('Year in college:'));
        if ($applicant->field_yr_in_college->entity) {
          $pdf->SetFont('Arial', '', 12);
          $pdf->Cell(80, 10, $this->convertEncoding($applicant->field_yr_in_college->entity->label()));
        }
        $pdf->Ln();

        $major = [];
        /** @var \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem $item */
        foreach ($applicant->field_major as $item) {
          $major[] = $item->entity->label();
        }
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(20, 10, $this->t('Major:'));
        if ($major) {
          $pdf->SetFont('Arial', '', 12);
          $pdf->Cell(80, 10, $this->convertEncoding(implode(', ', $major)));
          $pdf->Ln();
        }

      } // In review?

      $pdf->SetFont('Arial', 'B', 12);
      $pdf->Cell(40, 10, $this->t('Application text:'));
      if ($text = $flagging->field_essay->value) {
        $pdf->Ln();
        $this->addText($pdf, $text, TRUE);
      }
      $pdf->Ln();

      if ($display == 'page_review') {
        if ($resume = $applicant->field_resume->entity) {
          $this->addFile($pdf, $resume);
        }
        if ($transcript = $applicant->field_transcript->entity) {
          $this->addFile($pdf, $transcript);
        }
      } // In review?

    } // Loop thru rows.

    $site_config = $this->configFactory->get('system.site');
    $site_name = $site_config->get('name') ?? $this->t('Muser');
    $stage_text = ($display == 'page_review') ? $this->t('Review') : $this->t('Pending');
    $filename = $this->t('@site_name - Applications - @stage - @date', [
      '@site_name' => $site_name,
      '@stage' => $stage_text,
      '@date' => date('Y-m-d'),
    ]);
    $full_path = $this->fileSystem->createFilename($filename . '.pdf', './');
    $filename = pathinfo($full_path, PATHINFO_BASENAME);
    $content = $pdf->Output('S', $filename);

    $response = new Response();
    $response->headers->set('Content-Type', 'application/pdf');
    $response->headers->set('Content-Disposition', 'attachment;filename="' . $filename . '"');
    $response->setContent($content);
    return $response;

  }

  /**
   * Convert encoding of text.
   *
   * @param string $text
   *   Text in.
   *
   * @return array|false|string|string[]|null
   *   Text out.
   */
  public function convertEncoding(string $text) {
    return mb_convert_encoding($text, self::ENCODING);
  }

  /**
   * Adds text field to PDF.
   *
   * @param \setasign\Fpdi\Fpdi $pdf
   *   PDF object.
   * @param string $text
   *   Text to add.
   */
  public function addText(Fpdi &$pdf, string $text, $strip_tags = FALSE) {
    $tr = [
      "\n" => chr(30),
      "\r" => chr(30),
    ];
    $text = strtr($text, $tr);
    if ($strip_tags) {
      $text = preg_replace('~<style>.*?</style>~i', '', $text);
      $text = preg_replace('~<head>.*?</head>~i', '', $text);
      $text = preg_replace('~</p>|<br>~i', "\n\n", $text);
    }
    $text = preg_replace('~\r~', "\n", $text);
    $text = strtr($text, array_flip($tr));
    $text = preg_replace('~\n{2,}~', "\n", $text);
    if ($strip_tags) {
      $text = $this->convertEncoding($text);
      $text = strip_tags($text);
      $text = trim(html_entity_decode($text, ENT_QUOTES, self::ENCODING));
    }
    $pdf->SetFont('Arial', '', 12);
    $pdf->Write(5, $text);
//    $lines = explode("\n", $text);
//    foreach ($lines as $line) {
//      $pdf->Write(5, trim($line));
//      $pdf->Ln();
//      $pdf->Ln();
//    }
  }

  /**
   * Adds file to PDF.
   *
   * @param \setasign\Fpdi\Fpdi $pdf
   *   PDF object.
   * @param \Drupal\file\Entity\File $file
   *   File object.
   *
   * @throws \setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException
   * @throws \setasign\Fpdi\PdfParser\Filter\FilterException
   * @throws \setasign\Fpdi\PdfParser\PdfParserException
   * @throws \setasign\Fpdi\PdfParser\Type\PdfTypeException
   * @throws \setasign\Fpdi\PdfReader\PdfReaderException
   */
  public function addFile(Fpdi &$pdf, $file) {

    if (!$path = $this->fileSystem->realpath($file->getFileUri())) {
      // Can't get path.
      return;
    }

    switch ($file->getMimeType()) {

      case 'application/pdf':
        $this->addPdfFile($pdf, $path);
        break;

      case 'text/plain':
        $this->addTextFile($pdf, $path);
        break;

      default:
        // Try it as a Word doc.
        $this->addWordDoc($pdf, $path);
        break;

    } // End switch on mime type.

  }

  /**
   * Adds PDF file.
   *
   * @param \setasign\Fpdi\Fpdi $pdf
   * @param $path
   *
   * @throws \setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException
   * @throws \setasign\Fpdi\PdfParser\Filter\FilterException
   * @throws \setasign\Fpdi\PdfParser\PdfParserException
   * @throws \setasign\Fpdi\PdfParser\Type\PdfTypeException
   * @throws \setasign\Fpdi\PdfReader\PdfReaderException
   */
  public function addPdfFile(Fpdi &$pdf, $path) {
    try {
      $page_count = $pdf->setSourceFile($path);
      // Iterate through all pages.
      for ($page_num = 1; $page_num <= $page_count; $page_num++) {
        // iIport a page.
        $template_id = $pdf->importPage($page_num);
        $pdf->AddPage();
        // Use the imported page and adjust the page size.
        $pdf->useTemplate($template_id, ['adjustPageSize' => TRUE]);
      } // Loop thru pages.
    }
    catch (\Exception $exception) {
      // Log the exception.
      $this->loggerFactory->error('The PDF "@path" could not be exported for User ID @uid. Exception: "@exception" in @file:@line', [
        '@path' => $path,
        '@uid' => $this->currentUser->id(),
        '@exception' => $exception->getMessage(),
        '@file' => $exception->getFile(),
        '@line' => $exception->getLine(),
      ]);
      // Add an error message to the PDF.
      $filename = pathinfo($path, PATHINFO_BASENAME);
      $pdf->AddPage();
      $pdf->SetFont('Arial', 'B', 16);
      $pdf->Cell(20, 10, $this->t('Warning'));
      $pdf->Ln();
      $pdf->SetFont('Arial', 'I', 12);
      $pdf->Cell(80, 10, $this->t('The PDF "@file" could not be added to this PDF.', ['@file' => $filename]));
      $pdf->Ln();
      $pdf->Cell(80, 10, $this->t('To view it, go to the website under Mentor tasks > Applications.'));
      $pdf->Ln();
    }
  }

  /**
   * Adds text file.
   *
   * @param \setasign\Fpdi\Fpdi $pdf
   * @param $path
   *
   * @throws \setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException
   * @throws \setasign\Fpdi\PdfParser\Filter\FilterException
   * @throws \setasign\Fpdi\PdfParser\PdfParserException
   * @throws \setasign\Fpdi\PdfParser\Type\PdfTypeException
   * @throws \setasign\Fpdi\PdfReader\PdfReaderException
   */
  public function addTextFile(Fpdi &$pdf, $path, $strip_tags = FALSE) {
    try {
      if ($text = file_get_contents($path)) {
        $pdf->AddPage();
        $this->addText($pdf, $text, $strip_tags);
      }
    }
    catch (\Exception $exception) {
      // Log the exception.
      $this->loggerFactory->error('The text file "@path" could not be exported for User ID @uid. Exception: "@exception" in @file:@line', [
        '@path' => $path,
        '@uid' => $this->currentUser->id(),
        '@exception' => $exception->getMessage(),
        '@file' => $exception->getFile(),
        '@line' => $exception->getLine(),
      ]);
      // Add an error message to the PDF.
      $filename = pathinfo($path, PATHINFO_BASENAME);
      $pdf->AddPage();
      $pdf->SetFont('Arial', 'B', 16);
      $pdf->Cell(20, 10, $this->t('Warning'));
      $pdf->Ln();
      $pdf->SetFont('Arial', 'I', 12);
      $pdf->Cell(80, 10, $this->t('The text file "@file" could not be added to this PDF.', ['@file' => $filename]));
      $pdf->Ln();
      $pdf->Cell(80, 10, $this->t('To view it, go to the website under Mentor tasks > Applications.'));
      $pdf->Ln();
    }

  }

  /**
   * Parse Word doc and return content as HTML.
   *
   * Note:
   * For old versions of .doc files, this may throw a notice:
   * > Warning: ZipArchive::getFromName(): Invalid or uninitialized Zip object
   * > in PhpOffice\Common\XMLReader->getDomFromZip()
   * This is because those old versions are not supported.
   */
  protected function addWordDoc(&$pdf, $path) {
    $tmp_file_out = '';
    try {
      $tmp_name = md5($path . '|' . microtime());
      $tmp_file_out = $this->fileSystem->getTempDirectory() . '/' . $tmp_name . '-out.pdf';
      // Set up PhpWord and read file.
      PhpWordSettings::setPdfRendererPath('../vendor/dompdf/dompdf');
      PhpWordSettings::setPdfRendererName('DomPDF');
      if (!class_exists('ZipArchive')) {
        PhpWordSettings::setZipClass(PhpWordSettings::PCLZIP);
      }
      $php_word = @IOFactory::load($path);
      // Output the file as a PDF.
      $xml_writer = IOFactory::createWriter($php_word, 'PDF');
      $xml_writer->save($tmp_file_out);
      // Add the PDF to the document.
      $this->addPdfFile($pdf, $tmp_file_out);
    }
    catch (\Exception $exception) {
      // Could not parse Word Doc.
      // Log the exception.
      $this->loggerFactory->error('The document "@path" could not be exported as a Word Doc for User ID @uid. Exception: "@exception" in @file:@line', [
        '@path' => $path,
        '@uid' => $this->currentUser->id(),
        '@exception' => $exception->getMessage(),
        '@file' => $exception->getFile(),
        '@line' => $exception->getLine(),
      ]);
      // Add an error message to the PDF.
      $filename = pathinfo($path, PATHINFO_BASENAME);
      $pdf->AddPage();
      $pdf->SetFont('Arial', 'B', 16);
      $pdf->Cell(20, 10, $this->t('Warning'));
      $pdf->Ln();
      $pdf->SetFont('Arial', 'I', 12);
      $pdf->Cell(80, 10, $this->t('The document "@file" could not be added to this PDF.', ['@file' => $filename]));
      $pdf->Ln();
      $pdf->Cell(80, 10, $this->t('To view it, go to the website under Mentor tasks > Applications.'));
      $pdf->Ln();
    }
    if ($tmp_file_out && is_file($tmp_file_out)) {
      $this->fileSystem->unlink($tmp_file_out);
    }
  }

}
