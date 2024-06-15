<?php

$departments = [
    ['id' => 1, 'name' => 'BCA'],
    ['id' => 2, 'name' => 'MCA'],
    ['id' => 3, 'name' => 'MBA'],
    ['id' => 4, 'name' => 'BTech']
];

$courses = [
    ['id' => 1, 'department_id' => 1, 'name' => 'BCA Semester 1', 'total_students' => 80],
    ['id' => 2, 'department_id' => 1, 'name' => 'BCA Semester 2', 'total_students' => 90],
    ['id' => 3, 'department_id' => 2, 'name' => 'MCA Semester 1', 'total_students' => 60],
    ['id' => 4, 'department_id' => 2, 'name' => 'MCA Semester 2', 'total_students' => 70],
    ['id' => 5, 'department_id' => 3, 'name' => 'MBA Semester 1', 'total_students' => 50],
    ['id' => 6, 'department_id' => 3, 'name' => 'MBA Semester 2', 'total_students' => 55],
    ['id' => 7, 'department_id' => 4, 'name' => 'BTech Semester 1', 'total_students' => 120],
    ['id' => 8, 'department_id' => 4, 'name' => 'BTech Semester 2', 'total_students' => 130]
];

$rooms = [
    ['id' => 1, 'name' => 'Room 101', 'capacity' => 80],
    ['id' => 2, 'name' => 'Room 102', 'capacity' => 80],
    ['id' => 3, 'name' => 'Room 103', 'capacity' => 150],
    ['id' => 4, 'name' => 'Room 104', 'capacity' => 160],
    ['id' => 5, 'name' => 'Room 105', 'capacity' => 70],
    ['id' => 6, 'name' => 'Room 106', 'capacity' => 100],
    ['id' => 7, 'name' => 'Room 107', 'capacity' => 100],
    ['id' => 8, 'name' => 'Room 108', 'capacity' => 70]
];

class GeneticAlgorithm {
    private $courses;
    private $rooms;
    private $populationSize;
    private $generations;
    private $mutationRate;

    public function __construct($courses, $rooms, $populationSize = 100, $generations = 1000, $mutationRate = 0.01) {
        $this->courses = $courses;
        $this->rooms = $rooms;
        $this->populationSize = $populationSize;
        $this->generations = $generations;
        $this->mutationRate = $mutationRate;
    }

    public function generateAllocation() {
        $population = $this->initializePopulation();

        for ($generation = 0; $generation < $this->generations; $generation++) {
            $population = $this->evolve($population);
        }

        return $this->getBestIndividual($population);
    }

    private function initializePopulation() {
        $population = [];
        for ($i = 0; $i < $this->populationSize; $i++) {
            $allocation = $this->createIndividual();
            $population[] = $allocation;
        }
        return $population;
    }

    private function createIndividual() {
        $allocation = [];
        $usedRooms = [];

        foreach ($this->courses as $course) {
            $validRooms = array_filter($this->rooms, function($room) use ($course, $usedRooms) {
                return $room['capacity'] >= $course['total_students'] && !in_array($room['id'], $usedRooms);
            });

            if (empty($validRooms)) {
                // No valid room found, just use any room to avoid breaking
                $room = $this->rooms[array_rand($this->rooms)];
            } else {
                $room = $validRooms[array_rand($validRooms)];
                $usedRooms[] = $room['id'];
            }

            $allocation[] = [
                'course_id' => $course['id'],
                'room_id' => $room['id']
            ];
        }

        return $allocation;
    }

    private function evolve($population) {
        $newPopulation = [];
        usort($population, [$this, 'compareFitness']);
        for ($i = 0; $i < count($population); $i += 2) {
            $parent1 = $population[$i];
            $parent2 = $population[$i + 1];
            $offspring1 = $this->crossover($parent1, $parent2);
            $offspring2 = $this->crossover($parent2, $parent1);
            $newPopulation[] = $this->mutate($offspring1);
            $newPopulation[] = $this->mutate($offspring2);
        }
        return $newPopulation;
    }

    private function crossover($parent1, $parent2) {
        $crossoverPoint = rand(0, count($parent1) - 1);
        $child = array_merge(array_slice($parent1, 0, $crossoverPoint), array_slice($parent2, $crossoverPoint));

        $usedRooms = [];
        foreach ($child as $gene) {
            $usedRooms[] = $gene['room_id'];
        }

        foreach ($child as &$gene) {
            if (count(array_keys($usedRooms, $gene['room_id'])) > 1) {
                $course = $this->getCourseById($gene['course_id']);
                $validRooms = array_filter($this->rooms, function($room) use ($course, $usedRooms) {
                    return $room['capacity'] >= $course['total_students'] && !in_array($room['id'], $usedRooms);
                });

                if (!empty($validRooms)) {
                    $room = $validRooms[array_rand($validRooms)];
                    $usedRooms[] = $room['id'];
                    $gene['room_id'] = $room['id'];
                }
            }
        }

        return $child;
    }

    private function mutate($individual) {
        foreach ($individual as &$gene) {
            if (rand(0, 100) / 100 < $this->mutationRate) {
                $course = $this->getCourseById($gene['course_id']);
                $usedRooms = array_column($individual, 'room_id');
                $validRooms = array_filter($this->rooms, function($room) use ($course, $usedRooms) {
                    return $room['capacity'] >= $course['total_students'] && !in_array($room['id'], $usedRooms);
                });

                if (!empty($validRooms)) {
                    $gene['room_id'] = $validRooms[array_rand($validRooms)]['id'];
                }
            }
        }
        return $individual;
    }

    private function compareFitness($a, $b) {
        return $this->calculateFitness($b) - $this->calculateFitness($a);
    }

    private function calculateFitness($allocation) {
        $fitness = 0;
        foreach ($allocation as $entry) {
            $course = $this->getCourseById($entry['course_id']);
            $room = $this->getRoomById($entry['room_id']);
            if ($course['total_students'] <= $room['capacity']) {
                $fitness += $course['total_students'];
            }
        }
        return $fitness;
    }

    private function getBestIndividual($population) {
        usort($population, [$this, 'compareFitness']);
        return $population[0];
    }

    private function getCourseById($courseId) {
        foreach ($this->courses as $course) {
            if ($course['id'] == $courseId) {
                return $course;
            }
        }
        return null;
    }

    private function getRoomById($roomId) {
        foreach ($this->rooms as $room) {
            if ($room['id'] == $roomId) {
                return $room;
            }
        }
        return null;
    }
}

$geneticAlgorithm = new GeneticAlgorithm($courses, $rooms);
$allocation = $geneticAlgorithm->generateAllocation();

echo "Generated Room Allocation:\n";
foreach ($allocation as $entry) {
    echo "Course ID: " . $entry['course_id'] . " -> Room ID: " . $entry['room_id'] . "\n";
}

?>
