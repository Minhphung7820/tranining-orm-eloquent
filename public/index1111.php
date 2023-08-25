<?php
function binarySearch($arr, $target) {
    $left = 0;
    $right = count($arr) - 1;

    while ($left <= $right) {
        $mid = floor(($left + $right) / 2);

        if ($arr[$mid] == $target) {
            return $mid; // Trả về chỉ số của phần tử nếu tìm thấy
        }

        if ($arr[$mid] < $target) {
            $left = $mid + 1; // Tìm kiếm phần phải của mảng
        } else {
            $right = $mid - 1; // Tìm kiếm phần trái của mảng
        }
    }

    return -1; // Trả về -1 nếu không tìm thấy
}

// Example usage
$sortedArray = [11, 22, 25, 34, 64, 90];
$target = 34;

$index = binarySearch($sortedArray, $target);

if ($index != -1) {
    echo "Element found at index $index\n";
} else {
    echo "Element not found\n";
}
