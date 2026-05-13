<?php

declare(strict_types = 1);

use Civi\Test;
use Civi\Test\HeadlessInterface;
use Civi\Test\TransactionalInterface;
use PHPUnit\Framework\TestCase;

/**
 * Test for CRM_Xdedupe_DedupeRun
 *
 * @group headless
 */
class CRM_Xdedupe_DedupeRunTest extends TestCase implements HeadlessInterface, TransactionalInterface {

  public function setUpHeadless(): Test\CiviEnvBuilder {
    return Test::headless()
      ->install(['de.systopia.xdedupe'])
      ->apply();
  }

  /**
   * Test updateTuple method
   *
   * @covers CRM_Xdedupe_DedupeRun::updateTuple
   */
  public function testUpdateTuple(): void {
    $dedupeRun = new CRM_Xdedupe_DedupeRun();
    $tableName = $dedupeRun->getTableName();

    // Prepare data
    CRM_Core_DAO::executeQuery("INSERT INTO `$tableName` (contact_id, match_count, contact_ids) VALUES (1, 2, '1,2')");

    $dedupeRun->updateTuple(1, [3, 4]);

    /** @var \DB_DataObject&object{contact_id: int, contact_ids: string} $result */
    $result = CRM_Core_DAO::executeQuery("SELECT contact_id, contact_ids FROM `$tableName` WHERE contact_id = 3");
    static::assertTrue($result->fetch());
    static::assertEquals('3,4', $result->contact_ids);
    static::assertEquals(3, $result->contact_id);

    /** @var \DB_DataObject&object{contact_id: int} $resultOriginal */
    $resultOriginal = CRM_Core_DAO::executeQuery("SELECT contact_id FROM `$tableName` WHERE contact_id = 1");
    static::assertFalse($resultOriginal->fetch());
  }

}
