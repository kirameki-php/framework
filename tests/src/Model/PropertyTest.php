<?php declare(strict_types=1);

namespace Tests\Kirameki\Model;

use DateTime;
use Kirameki\Model\Reflection;
use Kirameki\Support\Str;

class PropertyTest extends ModelTestCase
{
    public function testDefiningInt(): void
    {
        $reflection = new Reflection(SampleUser::class);
        $builder = $this->makeReflectionBuilder($reflection);
        $builder->property('id', 'int');
        SampleUser::setTestReflection($reflection);

        $model = new SampleUser();
        $value = random_int(0, 10000);
        $model->setProperty('id', $value);

        // with cache
        $propValue = $model->getProperty('id');
        self::assertIsInt($propValue);
        self::assertEquals($value, $propValue);

        // no cache
        $propValue = $model->getProperty('id');
        self::assertIsInt($propValue);
        self::assertEquals($value, $propValue);
    }

    public function testDefiningFloat(): void
    {
        $reflection = new Reflection(SampleUser::class);
        $builder = $this->makeReflectionBuilder($reflection);
        $builder->property('id', 'float');
        SampleUser::setTestReflection($reflection);

        $model = new SampleUser();
        $value = random_int(0, 10000) / 1000.0;
        $model->setProperty('id', $value);

        // with cache
        $propValue = $model->getProperty('id');
        self::assertIsFloat($propValue);
        self::assertEquals($value, $propValue);

        // no cache
        $propValue = $model->getProperty('id');
        self::assertIsFloat($propValue);
        self::assertEquals($value, $propValue);
    }

    public function testDefiningBool(): void
    {
        $reflection = new Reflection(SampleUser::class);
        $builder = $this->makeReflectionBuilder($reflection);
        $builder->property('flag', 'bool');
        SampleUser::setTestReflection($reflection);

        $model = new SampleUser();
        $value = true;
        $model->setProperty('flag', $value);

        // with cache
        $propValue = $model->getProperty('flag');
        self::assertIsBool($propValue);
        self::assertEquals($value, $propValue);

        // no cache
        $propValue = $model->getProperty('flag');
        self::assertIsBool($propValue);
        self::assertEquals($value, $propValue);
    }

    public function testDefiningString(): void
    {
        $reflection = new Reflection(SampleUser::class);
        $builder = $this->makeReflectionBuilder($reflection);
        $builder->property('id', 'string');
        SampleUser::setTestReflection($reflection);

        $model = new SampleUser();
        $value = Str::uuid();
        $model->setProperty('id', $value);

        // with cache
        $propValue = $model->getProperty('id');
        self::assertIsString($propValue);
        self::assertEquals($value, $propValue);

        // no cache
        $propValue = $model->getProperty('id');
        self::assertIsString($propValue);
        self::assertEquals($value, $propValue);
    }

    public function testDefiningDateTime(): void
    {
        $reflection = new Reflection(SampleUser::class);
        $builder = $this->makeReflectionBuilder($reflection);
        $builder->property('createdAt', 'datetime');
        SampleUser::setTestReflection($reflection);

        $model = new SampleUser();
        $value = new DateTime();
        $model->setProperty('createdAt', $value);

        // with cache
        $propValue = $model->getProperty('createdAt');
        self::assertInstanceOf(DateTime::class, $propValue);
        self::assertEquals($value, $propValue);

        // no cache
        $propValue = $model->getProperty('createdAt');
        self::assertInstanceOf(DateTime::class, $propValue);
        self::assertEquals($value, $propValue);
    }

    public function testDefiningArray(): void
    {
        $reflection = new Reflection(SampleUser::class);
        $builder = $this->makeReflectionBuilder($reflection);
        $builder->property('ids', 'array');
        SampleUser::setTestReflection($reflection);

        $model = new SampleUser();
        $value = [1, 2];
        $model->setProperty('ids', $value);

        // with cache
        $propValue = $model->getProperty('ids');
        self::assertIsArray($propValue);
        self::assertEquals($value, $propValue);

        // no cache
        $propValue = $model->getProperty('ids');
        self::assertIsArray($propValue);
        self::assertEquals($value, $propValue);

        // raw value
        $rawValue = $model->getInitialProperty('ids');
        self::assertNull($rawValue);
    }
}
