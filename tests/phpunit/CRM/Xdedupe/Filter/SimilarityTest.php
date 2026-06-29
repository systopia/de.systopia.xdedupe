<?php

declare(strict_types = 1);

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @group headless
 * @covers CRM_Xdedupe_Filter_Similarity
 */
class CRM_Xdedupe_Filter_SimilarityTest extends TestCase {

  private function getFilterMock(): CRM_Xdedupe_Filter_Similarity&MockObject {
    $methods = [];
    return $this->getMockBuilder(CRM_Xdedupe_Filter_Similarity::class)
      ->setConstructorArgs([NULL, NULL])
      ->onlyMethods(array_merge(['similarity', 'getName', 'getHelp'], $methods))
      ->getMockForAbstractClass();
  }

  public function testPurgeResults(): void {
    $filter = $this->getFilterMock();
    $filter->method('similarity')->willReturn(0.0);

    // Mock CRM_Xdedupe_DedupeRun
    $run = $this->createMock(CRM_Xdedupe_DedupeRun::class);

    // Define behavior
    $run->expects(static::once())
      ->method('getTupleCount')
      ->willReturn(1);

    $run->expects(static::once())
      ->method('getTuples')
      ->with(250, 0)
      ->willReturn([100 => [200]]);

    // We expect removeTuple because for [200] and [100], if similarity is 0 (default),
    // it won't be >= 0.95, so buildSimilarityMatrix will be empty,
    // getBestTuple will return NULL.
    $run->expects(static::once())
      ->method('removeTuple')
      ->with(100);

    $filter->purgeResults($run);
  }

  public function testPurgeResultsUpdate(): void {
    $similarityMap = [
      100 => [200 => 1.0, 300 => 0.0],
      200 => [100 => 1.0, 300 => 0.0],
      300 => [100 => 0.0, 200 => 0.0],
    ];

    $filter = $this->getFilterMock();
    $filter->method('similarity')
      ->willReturnCallback(function($a, $b) use ($similarityMap) {
        return $similarityMap[$a][$b] ?? 0.0;
      });
    $run = $this->createMock(CRM_Xdedupe_DedupeRun::class);

    // Define behavior
    $run->method('getTupleCount')->willReturn(1);

    $run->expects(static::once())
      ->method('getTuples')
      ->willReturn([100 => [200, 300]]);

    $run->expects(static::once())
      ->method('updateTuple')
      ->with(100, [100, 200]);

    $filter->purgeResults($run);
  }

  /**
   * @param array<int> $contact_ids
   * @param float $threshold
   * @param array<int, array<int, float>> $similarityMap
   * @param array<int, array<int, float>> $expected
   *
   * @dataProvider buildSimilarityMatrixDataProvider
   */
  public function testBuildSimilarityMatrix(
    array $contact_ids,
    float $threshold,
    array $similarityMap,
    array $expected
  ): void {
    $filter = $this->getFilterMock();
    $filter->method('similarity')
      ->willReturnCallback(function($a, $b) use ($similarityMap) {
        return $similarityMap[$a][$b] ?? ($similarityMap[$b][$a] ?? 0.0);
      });

    // Reflection to set threshold
    $reflectionThreshold = new ReflectionProperty(CRM_Xdedupe_Filter_Similarity::class, 'threshold');
    $reflectionThreshold->setAccessible(TRUE);
    $reflectionThreshold->setValue($filter, $threshold);

    // Reflection to access protected method
    $reflection = new ReflectionMethod(CRM_Xdedupe_Filter_Similarity::class, 'buildSimilarityMatrix');
    $reflection->setAccessible(TRUE);

    $result = $reflection->invoke($filter, $contact_ids);

    static::assertEquals($expected, $result);
  }

  /**
   * @return array<string, array<string, mixed>>
   */
  public static function buildSimilarityMatrixDataProvider(): array {
    return [
      'standard_case' => [
        'contact_ids' => [1, 2, 3],
        'threshold' => 0.6,
        'similarityMap' => [
          1 => [2 => 0.9, 3 => 0.5],
          2 => [3 => 0.8],
        ],
        'expected' => [
          1 => [2 => 0.9],
          2 => [1 => 0.9, 3 => 0.8],
          3 => [2 => 0.8],
        ],
      ],
      'below_threshold' => [
        'contact_ids' => [1, 2],
        'threshold' => 0.9,
        'similarityMap' => [
          1 => [2 => 0.5],
        ],
        'expected' => [],
      ],
    ];
  }

  /**
   * @param array<int> $tuple
   * @param array<int, array<int, float>> $matrix
   * @param float $expected
   *
   * @dataProvider rateTupleDataProvider
   */
  public function testRateTuple(array $tuple, array $matrix, float $expected): void {
    $filter = $this->getFilterMock();

    // Reflection to access protected method
    $reflection = new \ReflectionMethod(CRM_Xdedupe_Filter_Similarity::class, 'rateTuple');
    $reflection->setAccessible(TRUE);

    $result = $reflection->invoke($filter, $tuple, $matrix);

    static::assertEquals($expected, $result);
  }

  /**
   * @return array<string, array<string, mixed>>
   */
  public static function rateTupleDataProvider(): array {
    return [
      'two_contacts' => [
        'tuple' => [1, 2],
        'matrix' => [
          1 => [2 => 0.8],
          2 => [1 => 0.8],
        ],
        'expected' => 0.8,
      ],
      'three_contacts' => [
        'tuple' => [1, 2, 3],
        'matrix' => [
          1 => [2 => 0.8, 3 => 0.6],
          2 => [1 => 0.8, 3 => 0.4],
          3 => [1 => 0.6, 2 => 0.4],
        ],
        // (0.8 + 0.6 + 0.4) / 3 = 1.8 / 3 = 0.6
        'expected' => 0.6,
      ],
      'one_contact' => [
        'tuple' => [1],
        'matrix' => [],
        'expected' => 0.0,
      ],
    ];
  }

}
