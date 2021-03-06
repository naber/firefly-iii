<?php
/**
 * FileJobConfigurationTest.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tests\Unit\Import\JobConfiguration;


use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Import\JobConfiguration\FileJobConfiguration;
use FireflyIII\Models\ImportJob;
use FireflyIII\Support\Import\JobConfiguration\File\ConfigureMappingHandler;
use FireflyIII\Support\Import\JobConfiguration\File\ConfigureRolesHandler;
use FireflyIII\Support\Import\JobConfiguration\File\ConfigureUploadHandler;
use FireflyIII\Support\Import\JobConfiguration\File\NewFileJobHandler;
use Illuminate\Support\MessageBag;
use Mockery;
use Tests\TestCase;

/**
 * Class FileJobConfigurationTest
 */
class FileJobConfigurationTest extends TestCase
{
    /**
     * No config, job is new.
     *
     * @covers \FireflyIII\Import\JobConfiguration\FileJobConfiguration
     */
    public function testCCFalse(): void
    {
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'File_A_unit_' . random_int(1, 1000);
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'fake';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        // should be false:
        $configurator = new FileJobConfiguration;
        $configurator->setImportJob($job);
        $this->assertFalse($configurator->configurationComplete());
    }

    /**
     * Job is ready to run.
     *
     * @covers \FireflyIII\Import\JobConfiguration\FileJobConfiguration
     */
    public function testCCTrue(): void
    {
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'File_B_unit_' . random_int(1, 1000);
        $job->status        = 'new';
        $job->stage         = 'ready_to_run';
        $job->provider      = 'fake';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        // should be false:
        $configurator = new FileJobConfiguration;
        $configurator->setImportJob($job);
        $this->assertTrue($configurator->configurationComplete());
    }

    /**
     * Configure the job when the stage is "map". Won't test other combo's because they're covered by other tests.
     *
     * @covers \FireflyIII\Import\JobConfiguration\FileJobConfiguration
     */
    public function testConfigureJob(): void
    {
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'I-file_' . random_int(1, 1000);
        $job->status        = 'new';
        $job->stage         = 'map';
        $job->provider      = 'file';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        $bag          = new MessageBag;
        $result       = null;

        $configurator = new FileJobConfiguration;
        $configurator->setImportJob($job);

        $handler = $this->mock(ConfigureMappingHandler::class);
        $handler->shouldReceive('setImportJob')->once()->withArgs([Mockery::any()]);
        $handler->shouldReceive('configureJob')->withArgs([['c' => 'd']])->andReturn($bag)->once();

        try {
            $result = $configurator->configureJob(['c' => 'd']);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertEquals($bag, $result);
    }

    /**
     * Get next data when stage is "configure-upload". Expect a certain class to be called.
     *
     * @covers \FireflyIII\Import\JobConfiguration\FileJobConfiguration
     */
    public function testGetNextDataCU(): void
    {
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'G-file_' . random_int(1, 1000);
        $job->status        = 'new';
        $job->stage         = 'configure-upload';
        $job->provider      = 'file';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        $result       = 'x';
        $configurator = new FileJobConfiguration;
        $configurator->setImportJob($job);

        $handler = $this->mock(ConfigureUploadHandler::class);
        $handler->shouldReceive('setImportJob')->once()->withArgs([Mockery::any()]);
        $handler->shouldReceive('getNextData')->andReturn(['a' => 'b'])->withNoArgs()->once();

        try {
            $result = $configurator->getNextData();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertEquals(['a' => 'b'], $result);
    }

    /**
     * Get next data when stage is "map". Expect a certain class to be called.
     *
     * @covers \FireflyIII\Import\JobConfiguration\FileJobConfiguration
     */
    public function testGetNextDataMap(): void
    {
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'H-file_' . random_int(1, 1000);
        $job->status        = 'new';
        $job->stage         = 'map';
        $job->provider      = 'file';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        $result       = 'x';
        $configurator = new FileJobConfiguration;
        $configurator->setImportJob($job);

        $handler = $this->mock(ConfigureMappingHandler::class);
        $handler->shouldReceive('setImportJob')->once()->withArgs([Mockery::any()]);
        $handler->shouldReceive('getNextData')->andReturn(['a' => 'b'])->withNoArgs()->once();

        try {
            $result = $configurator->getNextData();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertEquals(['a' => 'b'], $result);
    }

    /**
     * Get next data when stage is "new". Expect a certain class to be called.
     *
     * @covers \FireflyIII\Import\JobConfiguration\FileJobConfiguration
     */
    public function testGetNextDataNew(): void
    {
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'F-file_' . random_int(1, 1000);
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'file';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        $result       = 'x';
        $configurator = new FileJobConfiguration;
        $configurator->setImportJob($job);

        $handler = $this->mock(NewFileJobHandler::class);
        $handler->shouldReceive('setImportJob')->once()->withArgs([Mockery::any()]);
        $handler->shouldReceive('getNextData')->andReturn(['a' => 'b'])->withNoArgs()->once();

        try {
            $result = $configurator->getNextData();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertEquals(['a' => 'b'], $result);
    }

    /**
     * Get next data when stage is "roles". Expect a certain class to be called.
     *
     * @covers \FireflyIII\Import\JobConfiguration\FileJobConfiguration
     */
    public function testGetNextDataRoles(): void
    {
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'H-file_' . random_int(1, 1000);
        $job->status        = 'new';
        $job->stage         = 'roles';
        $job->provider      = 'file';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        $result       = 'x';
        $configurator = new FileJobConfiguration;
        $configurator->setImportJob($job);

        $handler = $this->mock(ConfigureRolesHandler::class);
        $handler->shouldReceive('setImportJob')->once()->withArgs([Mockery::any()]);
        $handler->shouldReceive('getNextData')->andReturn(['a' => 'b'])->withNoArgs()->once();

        try {
            $result = $configurator->getNextData();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertEquals(['a' => 'b'], $result);
    }

    /**
     * Get view when stage is "configure-upload".
     *
     * @covers \FireflyIII\Import\JobConfiguration\FileJobConfiguration
     */
    public function testGetNextViewCU(): void
    {
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'Dfile_' . random_int(1, 1000);
        $job->status        = 'new';
        $job->stage         = 'configure-upload';
        $job->provider      = 'file';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        $result       = 'x';
        $configurator = new FileJobConfiguration;
        $configurator->setImportJob($job);
        try {
            $result = $configurator->getNextView();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertEquals('import.file.configure-upload', $result);
    }

    /**
     * Get view when stage is "map".
     *
     * @covers \FireflyIII\Import\JobConfiguration\FileJobConfiguration
     */
    public function testGetNextViewMap(): void
    {
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'Ffile_' . random_int(1, 1000);
        $job->status        = 'new';
        $job->stage         = 'map';
        $job->provider      = 'file';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        $result       = 'x';
        $configurator = new FileJobConfiguration;
        $configurator->setImportJob($job);
        try {
            $result = $configurator->getNextView();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertEquals('import.file.map', $result);
    }

    /**
     * Get view when stage is "new".
     *
     * @covers \FireflyIII\Import\JobConfiguration\FileJobConfiguration
     */
    public function testGetNextViewNew(): void
    {
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'Cfile_' . random_int(1, 1000);
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'file';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        $result       = 'x';
        $configurator = new FileJobConfiguration;
        $configurator->setImportJob($job);
        try {
            $result = $configurator->getNextView();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertEquals('import.file.new', $result);
    }

    /**
     * Get view when stage is "roles".
     *
     * @covers \FireflyIII\Import\JobConfiguration\FileJobConfiguration
     */
    public function testGetNextViewRoles(): void
    {
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'Efile_' . random_int(1, 1000);
        $job->status        = 'new';
        $job->stage         = 'roles';
        $job->provider      = 'file';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        $result       = 'x';
        $configurator = new FileJobConfiguration;
        $configurator->setImportJob($job);
        try {
            $result = $configurator->getNextView();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertEquals('import.file.roles', $result);
    }
}
