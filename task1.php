<?php

echo "Введите список путей для директорий через 'Enter', в которых требуется посчитать сумму переменных из файлов с названием count всех поддиректорий\n";

$directories = getDirectories();
$uniqueDirectories = filterDirectories($directories);
$result = calculateSum($uniqueDirectories);

echo "Сумма все чисел из файлов count в указанных директориях: " . $result . "\n";

// формируем массив абсолютных путей для существующих директорий
function getDirectories(): array
{
    $directories = [];
    while (($line = fgets(STDIN)) !== "\n") {
        $directoryAbsolutePath = realpath(trim($line));
        if (is_string($directoryAbsolutePath)) {
            $directories[] = $directoryAbsolutePath;
        }
    }
    return $directories;
}

function filterDirectories(array $directories): array {
    // сортируем массив чтобы можно было последовательно отсеивать лишние директории
    sort($directories);

    // отсеиваем директории, которые являются подмножеством какой-либо другой директории (чтобы не складывать числа несколько раз)
    $suffix = $directories[0];
    foreach ($directories as $key => $directory) {
        $pos = strrpos($directory, $suffix . '/');

        if ($pos === 0) {
            unset($directories[$key]);
        } else {
            $suffix = $directory;
        }
    }


    return $directories;
}

// пробегаемся по всем непересекающимся директориям вглубь и посчитываем сумму чисел из файлов count
function calculateSum(array $directories): string {
    $result = '0';
    foreach ($directories as $directory) {
        $result = sumByString($result, numberAdder($directory));
    }
    return $result;
}

// выбираем что нам нужно: сложение или вычитание
function sumByString(string $a, string $b): string
{
    // если одна из строк пуста, то возвращаем другубю
    if (empty($a)) return $b;
    if (empty($b)) return $a;

    $isNegativeA = $a[0] === '-';
    $isNegativeB = $b[0] === '-';

    $a = $isNegativeA ? substr($a, 1) : $a;
    $b = $isNegativeB ? substr($b, 1) : $b;

    if ($isNegativeA !== $isNegativeB) {
        $result = subtraction($a, $b, $isNegativeA);
    } else {
        $result = addition($a, $b, $isNegativeA);
    }

    return $result;
}

// последовательно вычитаем меньшее по модулю число из большего
function subtraction($a, $b, bool $isNegativeA): string
{
    $totalSub = '';

    $maxLength = max(strlen($a), strlen($b));

    // добавляем ведущие нули чтобы строки были одной длины
    $a = str_pad($a, $maxLength, '0', STR_PAD_LEFT);
    $b = str_pad($b, $maxLength, '0', STR_PAD_LEFT);

    if ($a === $b) {
         return "0";
    } elseif ($a < $b) {
        [$minuend, $subtrahend, $isNegativeResult] = [$b, $a, !$isNegativeA];
    } else {
        [$minuend, $subtrahend, $isNegativeResult] = [$a, $b, $isNegativeA];
    }

    for($i = 1; $i <= $maxLength; $i++) {
        $minuendPosition = strlen($minuend) - $i;
        $subtrahendPosition = strlen($subtrahend) - $i;
        $currentMinuend = (int) $minuend[$minuendPosition];
        $currentSubtrahend = $subtrahendPosition >= 0 ? (int) $subtrahend[$subtrahendPosition] : 0;

        if ($currentMinuend >= $currentSubtrahend) {
            $sub = $currentMinuend - $currentSubtrahend;
        } else {
            for ($j = $minuendPosition - 1; $j < $maxLength; $j--) {
                $previousMinuted = (int) $minuend[$j];
                if ($previousMinuted > 0) {
                    $minuend[$j] = $previousMinuted - 1;

                    break;
                } else {
                    $minuend[$j] = 9;
                }
            }

            $sub = 10 + $currentMinuend - $currentSubtrahend;
        }

        $totalSub .= $sub;
    }

    $totalSub = strrev($totalSub);
    $totalSub = ltrim($totalSub, '0');

    return $isNegativeResult ? '-' . $totalSub : $totalSub;
}

// последовательно суммируем числа как строки (знак такой суммы зависит от того все слагаемые положительные или отрицательные)
function addition($a, $b, $isNegativeAddition): string
{
    $excess = 0;
    $totalSum = '';

    // добавляем ведущие нули чтобы строки были одной длины
    $maxLength = max(strlen($a), strlen($b));
    $a = str_pad($a, $maxLength, '0', STR_PAD_LEFT);
    $b = str_pad($b, $maxLength, '0', STR_PAD_LEFT);

    // пробегаемся с конца по массивам цифр и складываем как в столбик
    for ($i = $maxLength - 1; $i >= 0; $i--) {
        $sum = (int) $a[$i] + (int) $b[$i] + $excess;
        $excess = intdiv($sum, 10);
        $totalSum = ($sum % 10) . $totalSum;
    }

    // добавляем последний перенос, если ог есть
    if ($excess) {
        $totalSum = $excess . $totalSum;
    }

    // удаляем ведущие нули и добавляем знак, если нужно
    $totalSum = ltrim($totalSum, '0');
    return $isNegativeAddition ? '-' . $totalSum : $totalSum;
}

// отсеиваем файлы, в которых записаны не числа
function isNumber($content) {
    $pattern = '/^-?0*[0-9]+$/';

    return preg_match($pattern, $content);
}

// достаем содержимое файла и проверяем является ли оно числом (если нет то в суммировании оно не участвует)
function getNumberFromFile($filePath)
{
    $content = file_get_contents($filePath);
    $content = str_replace(array("\r", "\n"), '', $content);

    return isNumber($content) ? $content : '0';
}

// рекурсивная функция обхода поддиректорий и поиска файлов count для сложения содержимого
function numberAdder($directory): string
{
    $result = '0';

    $subDirectories = scandir($directory);
    if ($subDirectories) {
        foreach ($subDirectories as $key => $subDirectory) {
            if ($subDirectory === '.' || $subDirectory === '..') {
                unset($subDirectories[$key]);

                continue;
            }

            $subDirectoryAbsolutePath = $directory . "/" . $subDirectory;
            if (is_dir($subDirectoryAbsolutePath)) {
                $result = sumByString($result, numberAdder($subDirectoryAbsolutePath));;
            } elseif ($subDirectory === 'count') {
                $number = getNumberFromFile($subDirectoryAbsolutePath);
                $result = sumByString($number, $result);
            }
        }
    }

    return $result;
}
