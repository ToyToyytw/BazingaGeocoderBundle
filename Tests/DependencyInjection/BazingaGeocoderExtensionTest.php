<?php

namespace Bazinga\Bundle\GeocoderBundle\Tests\DependencyInjection;

use Symfony\Component\Yaml\Yaml;
use Doctrine\Common\Cache\ArrayCache;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Bazinga\Bundle\GeocoderBundle\DependencyInjection\BazingaGeocoderExtension;
use Bazinga\Bundle\GeocoderBundle\DependencyInjection\Compiler\AddDumperPass;
use Bazinga\Bundle\GeocoderBundle\DependencyInjection\Compiler\AddProvidersPass;

/**
 * @author Markus Bachmann <markus.bachmann@bachi.biz>
 */
class BazingaGeocoderExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testLoad()
    {
        $configs = Yaml::parse(file_get_contents(__DIR__.'/Fixtures/config.yml'));
        $container = new ContainerBuilder();
        $extension = new BazingaGeocoderExtension();

        $container->set('doctrine.apc.cache', new ArrayCache());

        $container->addCompilerPass(new AddDumperPass());
        $container->addCompilerPass(new AddProvidersPass());

        $extension->load($configs, $container);
        $container->compile();

        $this->assertInstanceOf(
            'Bazinga\Bundle\GeocoderBundle\EventListener\FakeRequestListener',
            $container->get('bazinga_geocoder.event_listener.fake_request')
        );

        $dumperManager = $container->get('bazinga_geocoder.dumper_manager');
        foreach (array('geojson', 'gpx', 'kmp', 'wkb', 'wkt') as $name) {
            $this->assertTrue($dumperManager->has($name));
        }

        $geocoder = $container->get('bazinga_geocoder.geocoder');
        $providers = $geocoder->getProviders();
        foreach (array(
            'bing_maps'      => 'Geocoder\\Provider\\BingMapsProvider',
            'cache'          => 'Bazinga\\Bundle\\GeocoderBundle\\Provider\\CacheProvider',
            'ip_info_db'     => 'Geocoder\\Provider\\IpInfoDbProvider',
            'yahoo'          => 'Geocoder\\Provider\\YahooProvider',
            'cloudmade'      => 'Geocoder\\Provider\\CloudMadeProvider',
            'google_maps'    => 'Geocoder\\Provider\\GoogleMapsProvider',
            'openstreetmaps' => 'Geocoder\\Provider\\OpenStreetMapsProvider',
            'host_ip'        => 'Geocoder\\Provider\\HostIpProvider',
            'free_geo_ip'    => 'Geocoder\\Provider\\FreeGeoIpProvider'
        ) as $name => $class) {
            $this->assertInstanceOf($class, $providers[$name], sprintf('-> Assert that %s is instance of %s', $name, $class));
        }
    }
}
