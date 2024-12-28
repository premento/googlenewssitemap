<?php

namespace Dionisiy\SitemapGoogle;

use XMLWriter;
use InvalidArgumentException;
use RuntimeException;
use DateTimeImmutable;

class Sitemap
{
    private string $filePath;
    private bool $useIndent = true;
    private bool $useXhtml;
    private XMLWriter $writer;
    private string $location;
    private string $title;
    private string $name;
    private DateTimeImmutable $date;
    private array $keywords = [];
    private string $genres;
    private string $lang;
    private array $images = [];

    public function __construct(string $filePath, bool $useXhtml = false)
    {
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            throw new InvalidArgumentException(
                "Please specify valid file path. Directory not exists. You have specified: {$dir}."
            );
        }
        $this->filePath = $filePath;
        $this->useXhtml = $useXhtml;
        $this->createNewFile();
    }

    public function setKeywords(array $keywords): void
    {
        $this->keywords = $keywords;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function setPublicationDate(DateTimeImmutable $date): void
    {
        $this->date = $date;
    }

    public function setGenres(string $genres): void
    {
        $this->genres = $genres;
    }

    public function setLanguage(string $lang): void
    {
        $this->lang = $lang;
    }

    public function setName(string $siteName): void
    {
        $this->name = $siteName;
    }

    public function setLoc(string $location): void
    {
        $this->location = $location;
    }

    public function setImages(array $images): void
    {
        $this->images = $images;
    }

    private function createNewFile(): void
    {
        $filePath = $this->filePath;
        if (file_exists($filePath)) {
            $filePath = realpath($filePath);
            if (is_writable($filePath)) {
                unlink($filePath);
            } else {
                throw new RuntimeException("File \"$filePath\" is not writable.");
            }
        }
        $this->writer = new XMLWriter();
        $this->writer->openMemory();
        $this->writer->startDocument('1.0', 'UTF-8');
        $this->writer->setIndent($this->useIndent);
        $this->writer->startElement('urlset');
        $this->writer->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $this->writer->writeAttribute('xmlns:news', 'http://www.google.com/schemas/sitemap-news/0.9');
        $this->writer->writeAttribute('xmlns:image', 'http://www.google.com/schemas/sitemap-image/1.1');
    }

    private function finishFile(): void
    {
        if ($this->writer !== null) {
            $this->writer->endElement();
            $this->writer->endDocument();
            $this->flush(true);
        }
    }

    public function write(): void
    {
        $this->finishFile();
    }

    private function flush(bool $finishFile = false): void
    {
        file_put_contents($this->filePath, $this->writer->flush(true), FILE_APPEND);
    }

    protected function validateLocation(string $location): void
    {
        if (false === filter_var($location, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException(
                "The location must be a valid URL. You have specified: {$location}."
            );
        }
    }

    public function addItem(): void
    {
        $this->validateLocation($this->location);

        $this->writer->startElement('url');
        $this->writer->writeElement('loc', $this->location);
        $this->writer->startElement('news:news');
        $this->writer->startElement('news:publication');
        $this->writer->writeElement('news:name', $this->name);
        $this->writer->writeElement('news:language', $this->lang);
        $this->writer->endElement();
        if (!empty($this->genres)) {
            $this->writer->writeElement('news:genres', $this->genres);
        }
        $this->writer->writeElement('news:publication_date', $this->date->format('c'));
        $this->writer->writeElement('news:title', $this->title);
        if (!empty($this->keywords)) {
            $this->writer->writeElement('news:keywords', implode(", ", $this->keywords));
        }
        $this->writer->endElement();

        if (!empty($this->images)) {
            foreach ($this->images as $image) {
                $this->writer->startElement('image:image');
                $this->writer->writeElement('image:loc', $image);
                $this->writer->endElement();
            }
        }

        $this->writer->endElement();
    }
}
