<?php declare(strict_types=1);

namespace Tests\Kirameki\Model;

use DateTime;
use Kirameki\Model\Reflection;
use Kirameki\Support\Collection;
use Kirameki\Support\Str;

class PropertyTest extends ModelTestCase
{
    public function test_defining_int(): void
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

    public function test_defining_float(): void
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

    public function test_defining_bool(): void
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

    public function test_defining_string(): void
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

    public function test_defining_DateTime(): void
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

    public function test_defining_Collection(): void
    {
        $reflection = new Reflection(SampleUser::class);
        $builder = $this->makeReflectionBuilder($reflection);
        $builder->property('ids', 'collection');
        SampleUser::setTestReflection($reflection);

        $model = new SampleUser();
        $value = new Collection([1, 2]);
        $model->setProperty('ids', $value);

        // with cache
        $propValue = $model->getProperty('ids');
        self::assertInstanceOf(Collection::class, $propValue);
        self::assertEquals($value, $propValue);

        // no cache
        $propValue = $model->getProperty('ids');
        self::assertInstanceOf(Collection::class, $propValue);
        self::assertEquals($value, $propValue);

        // raw value
        $rawValue = $model->getInitialProperty('ids');
        self::assertNull($rawValue);
    }
}
