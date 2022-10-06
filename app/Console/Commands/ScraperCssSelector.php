<?php

namespace App\Console\Commands;

use App\Services\Scraper;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Box\Spout\Writer\Common\Creator\Style\StyleBuilder;
use Box\Spout\Common\Entity\Row;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ScraperCssSelector extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scraper:selector {selector} {--startsWith=} {--site=} {--attrs=class} {--output=} {--showRelations} {--relationAttrs=class}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Find elements on our public-facing content based on CSS selectors';

    private ?Scraper $scraper = null;
    private array $attrs = ['class'];
    private array $relationAttrs = ['class'];

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->scraper = new Scraper();
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $attrs = $this->csvToArray($this->option('attrs'));
        $attrs && $this->attrs = $attrs;

        $relationAttrs = $this->csvToArray($this->option('relationAttrs'));
        $relationAttrs && $this->relationAttrs = $relationAttrs;

        $this->option('startsWith') && $this->scraper->setStartsWithFilter($this->option('startsWith'));
        $this->scraper->setUrls($this->option('site'));

        $urls = $this->scraper->getUrls();
        
        if ($this->option('output') === 'xlsx') {
            $this->reportXlsx($urls);
        } else {
            $this->report($urls);
        }  
        return 1;
    }

    private function report(Collection $urls) {
        $urls->each(function ($url) {
            $this->info($url);
            $data = $this->scraper->getDataBySelector($url, $this->argument('selector'), $this->attrs, $this->relationAttrs, $this->option('showRelations'));
            if (!$data->isEmpty()) {
                $this->table(
                    $data->get('headings'),
                    $data->get('values')
                );
            }
        });
    }

    private function reportXlsx(Collection $urls) {
        $writer = WriterEntityFactory::createXLSXWriter();
        $dir = Storage::disk('local')->path('/');
        $filename = sprintf(
            "%s-%s-report.xlsx",
            date('Y-m-d-His'),
            Str::substr(Str::slug($this->argument('selector')), 0, 20)
        );
        $writer->openToFile($dir . $filename);

        
        $urls->each(function ($url, $key) use ($writer) {
            $data = $this->scraper->getDataBySelector($url, $this->argument('selector'), $this->attrs, $this->relationAttrs, $this->option('showRelations'));
            if ($key === 0) {
                $style = (new StyleBuilder())
                    ->setFontBold()
                    ->build();
                $rowFromValues = WriterEntityFactory::createRowFromArray($data->get('headings'), $style);
                $writer->addRow($rowFromValues);
            }
            if (!empty($data->get('values'))) {
                collect($data->get('values'))->each(function ($values) use ($writer) {
                    $rowFromValues = WriterEntityFactory::createRowFromArray($values);
                    $writer->addRow($rowFromValues);
                });
            }
        });
        $writer->close();
    }

    private function csvToArray(string $string): array
	{
		$array = explode(',', $string);
		return array_map(function ($el) {
			return trim($el);
		}, $array);
	}
}
