<?php

declare(strict_types = 1);

use Civi\Test;
use Civi\Test\HeadlessInterface;
use PHPUnit\Framework\TestCase;

/**
 * Test for CRM_Xdedupe_Configuration
 *
 * @group headless
 */
class CRM_Xdedupe_ConfigurationTest extends TestCase implements HeadlessInterface {

  public function setUpHeadless(): Test\CiviEnvBuilder {
    return Test::headless()
      ->install(['de.systopia.xdedupe'])
      ->apply();
  }

  /**
   * Test that a scheduled run drops the tables of expired runs
   *
   * @covers CRM_Xdedupe_Configuration::run
   */
  public function testRunDropsExpiredTables(): void {
    $expiredRun = new CRM_Xdedupe_DedupeRun('20200101000000_' . str_repeat('0', 32));
    $configuration = new CRM_Xdedupe_Configuration(0, [
      'name' => 'Email',
      'finder_1' => 'CRM_Xdedupe_Finder_Email',
      'filters' => [],
    ]);
    $configurationId = $configuration->store();

    civicrm_api3('Xdedupe', 'run', [
      'cid' => $configurationId,
      'scheduled_override' => 1,
      'merge_limit' => 0,
    ]);

    CRM_Xdedupe_Configuration::delete($configurationId);
    static::assertFalse(CRM_Core_DAO::checkTableExists($expiredRun->getTableName()));
  }

}
