<?php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ProductVariantRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

#[AsCommand(
    name: 'app:fill-variant-slugs',
    description: 'Remplit les slugs manquants des ProductVariant',
)]
class FillVariantSlugsCommand extends Command
{
    public function __construct(
        private ProductVariantRepository $productVariantRepository,
        private EntityManagerInterface $em,
        private SluggerInterface $slugger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $variants = $this->productVariantRepository->findAll();
        $count = 0;

        foreach ($variants as $variant) {
            if (!$variant->getSlug()) {
                $product = $variant->getProduct();
                if ($product) {
                    $base = $product->getSlug() ?? $product->getName();
                } else {
                    $base = 'variant';
                }
                $slug = strtolower($this->slugger->slug($base . '-' . $variant->getId()));
                $variant->setSlug($slug);
                $count++;
            }
        }

        $this->em->flush();
        $output->writeln("<info>$count slugs ajoutés ou mis à jour avec succès.</info>");

        return Command::SUCCESS;
    }
}
