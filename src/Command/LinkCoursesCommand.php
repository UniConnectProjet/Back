<?php

namespace App\Command;

use App\Entity\Professor;
use App\Entity\Course;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:link-courses',
    description: 'Lie des cours aux professeurs',
)]
class LinkCoursesCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('Liaison des cours aux professeurs');
        
        // Récupérer les professeurs
        $professors = $this->em->getRepository(Professor::class)->findAll();
        $io->info(sprintf('Professeurs trouvés : %d', count($professors)));
        
        // Récupérer les cours
        $courses = $this->em->getRepository(Course::class)->findAll();
        $io->info(sprintf('Cours trouvés : %d', count($courses)));
        
        if (empty($professors) || empty($courses)) {
            $io->error('Aucun professeur ou cours trouvé !');
            return Command::FAILURE;
        }
        
        // Lier chaque professeur à quelques cours
        $assignments = [
            0 => [0, 1, 2, 3, 4],      // Premier professeur -> Cours 0-4
            1 => [5, 6, 7, 8, 9],      // Deuxième professeur -> Cours 5-9
            2 => [0, 2, 4, 6, 8],      // Troisième professeur -> Cours pairs
        ];
        
        $totalLinked = 0;
        
        foreach ($assignments as $professorIndex => $courseIndices) {
            if (isset($professors[$professorIndex])) {
                $professor = $professors[$professorIndex];
                $io->section(sprintf('Professeurs %d', $professor->getId()));
                
                foreach ($courseIndices as $courseIndex) {
                    if (isset($courses[$courseIndex])) {
                        $course = $courses[$courseIndex];
                        
                        // Vérifier si la liaison existe déjà
                        if (!$professor->getCourses()->contains($course)) {
                            $professor->addCourse($course);
                            $io->text(sprintf('  ✅ Lié au cours : %s (ID: %d)', $course->getName(), $course->getId()));
                            $totalLinked++;
                        } else {
                            $io->text(sprintf('  ⚠️  Déjà lié au cours : %s (ID: %d)', $course->getName(), $course->getId()));
                        }
                    }
                }
            }
        }
        
        // Sauvegarder les changements
        $this->em->flush();
        
        $io->success(sprintf('%d liaisons créées avec succès !', $totalLinked));
        
        // Afficher un résumé
        $io->section('Résumé des liaisons');
        $summary = $this->em->getRepository(Professor::class)
            ->createQueryBuilder('p')
            ->select('p.id, u.name, u.lastname, COUNT(c.id) as courseCount')
            ->leftJoin('p.userId', 'u')
            ->leftJoin('p.courses', 'c')
            ->groupBy('p.id, u.name, u.lastname')
            ->orderBy('p.id')
            ->getQuery()
            ->getResult();
        
        foreach ($summary as $row) {
            $io->text(sprintf('  %s %s (ID: %d) : %d cours', 
                $row['name'], $row['lastname'], $row['id'], $row['courseCount']));
        }
        
        return Command::SUCCESS;
    }
}
