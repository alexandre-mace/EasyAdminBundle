<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Command\MakeAdminDashboardCommand;
use EasyCorp\Bundle\EasyAdminBundle\Command\MakeAdminMigrationCommand;
use EasyCorp\Bundle\EasyAdminBundle\Command\MakeAdminResourceCommand;
use EasyCorp\Bundle\EasyAdminBundle\Context\ApplicationContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\EventListener\ApplicationContextListener;
use EasyCorp\Bundle\EasyAdminBundle\EventListener\CrudActionResponseListener;
use EasyCorp\Bundle\EasyAdminBundle\EventListener\ExceptionListener;
use EasyCorp\Bundle\EasyAdminBundle\Factory\ActionFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\ApplicationContextFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\EntityFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\FormFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\MenuFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\PaginatorFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\PropertyFactory;
use EasyCorp\Bundle\EasyAdminBundle\Form\Extension\EaCrudFormTypeExtension;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\FilterRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\CrudFormType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\FiltersFormType;
use EasyCorp\Bundle\EasyAdminBundle\Formatter\EntityPaginatorJsonFormatter;
use EasyCorp\Bundle\EasyAdminBundle\Formatter\IntlFormatter;
use EasyCorp\Bundle\EasyAdminBundle\Inspector\DataCollector;
use EasyCorp\Bundle\EasyAdminBundle\Orm\EntityPaginator;
use EasyCorp\Bundle\EasyAdminBundle\Orm\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Orm\EntityUpdater;
use EasyCorp\Bundle\EasyAdminBundle\Property\Configurator\AssociationConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Property\Configurator\AvatarConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Property\Configurator\BooleanConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Property\Configurator\CollectionConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Property\Configurator\CommonPostConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Property\Configurator\CommonPreConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Property\Configurator\CountryConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Property\Configurator\CurrencyConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Property\Configurator\DateTimeConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Property\Configurator\EmailConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Property\Configurator\ImageConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Property\Configurator\LanguageConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Property\Configurator\LocaleConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Property\Configurator\MoneyConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Property\Configurator\NumberConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Property\Configurator\PercentConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Property\Configurator\SelectConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Property\Configurator\TelephoneConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Property\Configurator\TextConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Property\Configurator\TimezoneConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Property\Configurator\UrlConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Router\CrudUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Security\AuthorizationChecker;
use EasyCorp\Bundle\EasyAdminBundle\Security\SecurityVoter;
use EasyCorp\Bundle\EasyAdminBundle\Twig\EasyAdminTwigExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\DependencyInjection\DependencyInjectionExtension;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

return static function (ContainerConfigurator $container) {
    $services = $container->services()
        ->defaults()->private();

    $services
        ->set(MakeAdminMigrationCommand::class)->public()
            ->tag('console.command', ['command' => 'make:admin:migration'])

        ->set(MakeAdminDashboardCommand::class)->public()
            ->tag('console.command', ['command' => 'make:admin:dashboard'])

        ->set(MakeAdminResourceCommand::class)->public()
            ->tag('console.command', ['command' => 'make:admin:resource'])

        ->set(DataCollector::class)
            ->arg(0, ref(ApplicationContextProvider::class))
            ->tag('data_collector', ['id' => 'easyadmin', 'template' => '@EasyAdmin/inspector/data_collector.html.twig'])

        ->set(ExceptionListener::class)
            ->arg(0, ref(ApplicationContextProvider::class))
            ->arg(1, ref('twig'))
            ->tag('kernel.event_listener', ['event' => 'kernel.exception', 'priority' => -64])

        ->set(EasyAdminTwigExtension::class)
            ->arg(0, ref(CrudUrlGenerator::class))
            ->arg(1, ref(AuthorizationChecker::class))
            ->arg(2, ref(PropertyAccessorInterface::class))
            ->arg(3, ref(TranslatorInterface::class)->nullOnInvalid())
            ->arg(4, '%kernel.debug%')
            ->tag('twig.extension')

        ->set(EaCrudFormTypeExtension::class)
            ->arg(0, ref(ApplicationContextProvider::class))
            ->tag('form.type_extension', ['alias' => 'form', 'extended-type' => FormType::class])

        ->set(AuthorizationChecker::class)
            ->arg(0, ref('security.authorization_checker')->nullOnInvalid())

        ->set(IntlFormatter::class)

        ->set(ApplicationContextProvider::class)
            ->arg(0, ref('request_stack'))

        ->set(ApplicationContextListener::class)
            ->arg(0, ref(ApplicationContextFactory::class))
            ->arg(1, ref('controller_resolver'))
            ->arg(2, ref('twig'))
            ->tag('kernel.event_listener', ['event' => ControllerEvent::class])

        ->set(CrudActionResponseListener::class)
            ->arg(0, ref(ApplicationContextProvider::class))
            ->arg(1, ref('twig'))
            ->tag('kernel.event_listener', ['event' => ViewEvent::class])

        ->set(ApplicationContextFactory::class)
            ->arg(0, ref('security.token_storage')->nullOnInvalid())
            ->arg(1, ref(MenuFactory::class))
            ->arg(2, tagged_iterator('ea.crud_controller'))

        ->set(CrudUrlGenerator::class)
            ->arg(0, ref(ApplicationContextProvider::class))
            ->arg(1, ref('router.default'))

        ->set(MenuFactory::class)
            ->arg(0, ref(ApplicationContextProvider::class))
            ->arg(1, ref(AuthorizationChecker::class))
            ->arg(2, ref('translator'))
            ->arg(3, ref('router'))
            ->arg(4, ref('security.logout_url_generator'))
            ->arg(5, ref(CrudUrlGenerator::class))

        ->set(FilterRegistry::class)
            // arguments are injected using the FilterTypePass compiler pass
            ->arg(0, null) // $filterTypeMap collection
            ->arg(1, null) // $filterTypeGuesser iterator

        // TODO: ask Yonel about this because I don't understand anything about this service :|
        ->set('ea.filter.extension', DependencyInjectionExtension::class)
            // arguments are injected using the FilterTypePass compiler pass
            ->arg(0, null) // service locator with all services tagged with 'ea.filter.type'
            ->arg(1, [])
            ->arg(2, [])

        ->set(EntityRepository::class)
            ->arg(0, ref(ApplicationContextProvider::class))
            ->arg(1, ref('doctrine'))
            ->arg(2, ref('form.factory'))
            ->arg(3, ref(FilterRegistry::class))

        ->set(EntityFactory::class)
            ->arg(0, ref(ApplicationContextProvider::class))
            ->arg(1, ref(PropertyFactory::class))
            ->arg(2, ref(ActionFactory::class))
            ->arg(3, ref(AuthorizationChecker::class))
            ->arg(4, ref('doctrine'))
            ->arg(5, ref('event_dispatcher'))

        ->set(EntityPaginator::class)
            ->arg(0, ref(CrudUrlGenerator::class))
            ->arg(1, ref(EntityFactory::class))

        ->set(EntityUpdater::class)
            ->arg(0, ref('property_accessor'))

        ->set(PaginatorFactory::class)
            ->arg(0, ref(ApplicationContextProvider::class))
            ->arg(1, ref(EntityPaginator::class))

        ->set(FormFactory::class)
            ->arg(0, ref(ApplicationContextProvider::class))
            ->arg(1, ref('form.factory'))
            ->arg(2, ref(CrudUrlGenerator::class))

        ->set(PropertyFactory::class)
            ->arg(0, ref(ApplicationContextProvider::class))
            ->arg(1, ref(AuthorizationChecker::class))
            ->arg(2, \function_exists('tagged') ? tagged('ea.property_configurator') : tagged_iterator('ea.property_configurator'))

        ->set(ActionFactory::class)
            ->arg(0, ref(ApplicationContextProvider::class))
            ->arg(1, ref(AuthorizationChecker::class))
            ->arg(2, ref('translator'))
            ->arg(3, ref('router'))
            ->arg(4, ref(CrudUrlGenerator::class))

        ->set(SecurityVoter::class)
            ->arg(0, ref(AuthorizationChecker::class))
            ->arg(1, ref(ApplicationContextProvider::class))
            ->tag('security.voter')

        ->set(CrudFormType::class)
            ->arg(0, \function_exists('tagged') ? tagged('ea.form_type_configurator') : tagged_iterator('ea.form_type_configurator'))
            ->arg(1, ref('form.type_guesser.doctrine'))
            ->tag('form.type', ['alias' => 'ea_crud'])

        ->set(CommonPreConfigurator::class)
            ->arg(0, ref(ApplicationContextProvider::class))
            ->arg(1, ref('translator'))
            ->arg(2, ref('property_accessor'))
            ->tag('ea.property_configurator', ['priority' => 9999])

        ->set(CommonPostConfigurator::class)
            ->tag('ea.property_configurator', ['priority' => -9999])

        ->set(TextConfigurator::class)
            ->tag('ea.property_configurator')

        ->set(ImageConfigurator::class)
            ->tag('ea.property_configurator')

        ->set(DateTimeConfigurator::class)
            ->arg(0, ref(ApplicationContextProvider::class))
            ->arg(1, ref(IntlFormatter::class))
            ->tag('ea.property_configurator')

        ->set(CountryConfigurator::class)
            ->tag('ea.property_configurator')

        ->set(BooleanConfigurator::class)
            ->tag('ea.property_configurator')

        ->set(AvatarConfigurator::class)
            ->tag('ea.property_configurator')

        ->set(EmailConfigurator::class)
            ->tag('ea.property_configurator')

        ->set(TelephoneConfigurator::class)
            ->tag('ea.property_configurator')

        ->set(UrlConfigurator::class)
            ->tag('ea.property_configurator')

        ->set(LanguageConfigurator::class)
            ->tag('ea.property_configurator')

        ->set(MoneyConfigurator::class)
            ->arg(0, ref(ApplicationContextProvider::class))
            ->arg(1, ref(IntlFormatter::class))
            ->arg(2, ref('property_accessor'))
            ->tag('ea.property_configurator')

        ->set(CurrencyConfigurator::class)
            ->tag('ea.property_configurator')

        ->set(PercentConfigurator::class)
            ->tag('ea.property_configurator')

        ->set(AssociationConfigurator::class)
            ->arg(0, ref(ApplicationContextProvider::class))
            ->arg(1, ref(EntityFactory::class))
            ->arg(2, ref(CrudUrlGenerator::class))
            ->arg(3, ref(TranslatorInterface::class))
            ->tag('ea.property_configurator')

        ->set(SelectConfigurator::class)
            ->arg(0, ref(ApplicationContextProvider::class))
            ->arg(1, ref('translator'))
            ->tag('ea.property_configurator')

        ->set(TimezoneConfigurator::class)
            ->tag('ea.property_configurator')

        ->set(LocaleConfigurator::class)
            ->tag('ea.property_configurator')

        ->set(NumberConfigurator::class)
            ->arg(0, ref(IntlFormatter::class))
            ->tag('ea.property_configurator')

        ->set(CollectionConfigurator::class)
            ->tag('ea.property_configurator')

        ->set(FiltersFormType::class)
            ->arg(0, \function_exists('tagged') ? tagged('ea.form_type_configurator') : tagged_iterator('ea.form_type_configurator'))
            ->tag('form.type', ['alias' => 'ea_filters'])
    ;
};