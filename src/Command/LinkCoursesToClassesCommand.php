<?php

namespace App\Command;

use App\Entity\Course;
use App\Entity\Classe;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:link-courses-classes',
    description: 'Lie des cours aux classes',
)]
class LinkCoursesToClassesCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('🔗 Liaison des cours aux classes');
        
        // Récupérer les cours
        $courses = $this->em->getRepository(Course::class)->findAll();
        $io->info(sprintf('📚 Cours trouvés : %d', count($courses)));
        
        // Récupérer les classes
        $classes = $this->em->getRepository(Classe::class)->findAll();
        $io->info(sprintf('📖 Classes trouvées : %d', count($classes)));
        
        if (empty($courses) || empty($classes)) {
            $io->error('Aucun cours ou classe trouvé !');
            return Command::FAILURE;
        }
        
        // Lier chaque cours à quelques classes
        $assignments = [
            0 => [0, 1, 2],      // Premier cours -> Classes 0-2
            1 => [1, 2, 3],      // Deuxième cours -> Classes 1-3
            2 => [0, 3, 4],      // Troisième cours -> Classes 0, 3-4
            3 => [2, 4, 5],      // Quatrième cours -> Classes 2, 4-5
            4 => [0, 1, 5],      // Cinquième cours -> Classes 0, 1, 5
        ];
        
        $totalLinked = 0;
        
        foreach ($assignments as $courseIndex => $classIndices) {
            if (isset($courses[$courseIndex])) {
                $course = $courses[$courseIndex];
                $io->section(sprintf('📚 Cours : %s (ID: %d)', $course->getName(), $course->getId()));
                
                foreach ($classIndices as $classIndex) {
                    if (isset($classes[$classIndex])) {
                        $classe = $classes[$classIndex];
                        
                        // Vérifier si la liaison existe déjà
                        if (!$course->getClasses()->contains($classe)) {
                            $course->addClass($classe);
                            $io->text(sprintf('  ✅ Lié à la classe : %s (ID: %d)', $classe->getName(), $classe->getId()));
                            $totalLinked++;
                        } else {
                            $io->text(sprintf('  ⚠️  Déjà lié à la classe : %s (ID: %d)', $classe->getName(), $classe->getId()));
                        }
                    }
                }
            }
        }
        
        // Sauvegarder les changements
        $this->em->flush();
        
        $io->success(sprintf('🎉 %d liaisons créées avec succès !', $totalLinked));
        
        // Afficher un résumé
        $io->section('📊 Résumé des liaisons');
        $summary = $this->em->getRepository(Course::class)
            ->createQueryBuilder('c')
            ->select('c.id, c.name, COUNT(cl.id) as classCount')
            ->leftJoin('c.class_id', 'cl')
            ->groupBy('c.id, c.name')
            ->orderBy('c.id')
            ->getQuery()
            ->getResult();
        
        foreach ($summary as $row) {
            $io->text(sprintf('  📚 %s (ID: %d) : %d classes', 
                $row['name'], $row['id'], $row['classCount']));
        }
        
        return Command::SUCCESS;
    }
}
