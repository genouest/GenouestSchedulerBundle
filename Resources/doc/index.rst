========
Overview
========

This bundle allows you to launch command lines on a cluster.

It was designed to support submission of jobs to SGE (Sun Grid Engine) clusters,
using a php-drmaa binding.
It is also possible to extend it to other scheduling system.


How does it work?
-----------------

The bundle provide some basic actions and associated basic templates to submit a job,
query its status (running, finished), kill it if needed, display the results, and see the history
of all the jobs a user has run.

All the job informations are stored in a database with Doctrine ORM.

To submit jobs to a SGE cluster, you need to install a dedicated PHP extension (see :ref:`installation-label`).


.. _installation-label:
Installation
------------

To us the Drmaa scheduler, you need to install the php4drm PHP extension (https://gforge.inria.fr/projects/php4drm). Download the source code from::

    https://gforge.inria.fr/frs/?group_id=1835

Install it, following the instructions in the README file.

Checkout a copy of the bundle code::

    git submodule add gitolite@chili.genouest.org:sf2-schedulerbundle vendor/bundles/Genouest/Bundle/SchedulerBundle
    
Then register the bundle with your kernel::

    // in AppKernel::registerBundles()
    $bundles = array(
        // ...
        new Genouest\Bundle\SchedulerBundle\GenouestSchedulerBundle(),
        // ...
    );

Make sure that you also register the namespaces with the autoloader::

    // app/autoload.php
    $loader->registerNamespaces(array(
        // ...
        'Genouest\\Bundle' => __DIR__.'/../vendor/bundles',
        // ...
    ));

Import the routes defined in the bundle::

    // app/config/routing.yml
    // ...
    _scheduler:
        resource: "@GenouestSchedulerBundle/Controller/SchedulerController.php"
        prefix: /scheduler
        type: annotation
    // ...
    
Publish the assets in the web dir:

    app/console assets:install --symlink web/


Configuration
-------------

You need to have a properly configured doctrine orm. By default, the scheduler will use the default entity_manager.
See below if you want to use a specific doctrine entity_manager

The following configuration keys are available::

    # app/config/config.yml
    genouest_scheduler:
        # The type of scheduler to use.
        # This can be "drmaa" if you want to launch jobs on a SGE cluster,
        # or "local" if you want to run the jobs on the web server (mainly for testing).
        # It can also be any class implementing Genouest\Bundle\SchedulerBundle\Scheduler\SchedulerInterface.
        # "drmaa" and "local" are shortcuts for the corresponding classes:
        #  Genouest\Bundle\SchedulerBundle\Scheduler\DrmaaScheduler
        #  Genouest\Bundle\SchedulerBundle\Scheduler\LocalScheduler
        method:               drmaa
        
        # Each job will have a specific work dir (random name) in the specified work_dir
        work_dir:             "/some/tmp/dir/"
        
        # Url to access the files located in work_dir
        result_url:           "http://example.org/temp/"
        
        # The mail binary that can be used to alert users when their jobs are finished
        # This binary must be available on the cluster nodes.
        mail_bin:             "/bin/mail"
        
        # The sender when sending emails to users
        from_email:           {"webmaster@example.org": "webmaster"}
        
        # In the history, only display jobs launched within the xx last days
        history_length:       8
        
        # SGE native specification (use it to specify a submission queue for example)
        # Only used by drmaa scheduler
        drmaa_native:         "-q webjobs"
        
        # This is an optional temp dir, not accessible from the web, for some temporary files.
        # Default: the same as work_dir
        # Only used by drmaa scheduler
        drmaa_temp_dir:       "/some/other/tmp/dir/"

Please note that if you change the scheduling method (drmaa/local/other), the old jobs that were launched with the
previous settings won't be accessible anymore (status or results page).

Usage
-----

Launching a job
~~~~~~~~~~~~~~~

To launch a job, you first need to create a Job object representing the job you want to launch. This is usually done after the submission of a forms, in an action::

    $scheduler = $this->get('scheduler.scheduler');
    $workDir = $scheduler->getWorkDir($job);
    
    $job = new Job();
    $job->setProgramName('blast'); // It is important to set program name *before* generating the uid
    $jobuid = $job->generateJobUid();
    $job->setTitle('Some title describing the job'); // Optional
    $job->setEmail('mail@example.org'); // To be alerted when the job is finished, optional
    $job->setBackUrl('http://example.org/the/submission/form'); // Url of a form to submit another jobs, optional
    $job->setCommand('echo test > '.$workDir.'output.txt; sleep 10; intensive-algorithm -output '.$workDir.'results.txt'); // The command line to launch
    $job->addResultFilesArray(array('Test output' => 'output.txt', 'Precious results' => 'results.txt')); // An array of expected result files
    $job->addResultViewersArray(array('Online viewer' => 'http://example.org/result/viewer/'.$jobuid)); // An array of result viewers

The command line must only use absolute path for input/output paths. In the code above, we retrieve the scheduler and ask him to give us the work dir
of the job we have just created. We use this work dir in the command line.

One your Job object is ready, you only need to forward the request to the launchJob action:

    return $this->forward('GenouestSchedulerBundle:Scheduler:launchJob', array('job' => $job));

And that's it! The job gets submitted to the configured scheduler, and you get redirected to a page tracking the status of your job.

Getting the status of a job
~~~~~~~~~~~~~~~~~~~~~~~~~~~

A status action is bundled in GenouestSchedulerBundle. You can access it like this for example::

    public function yourAction() {
        // ...Do some stuff
        
        // Redirect to status page
        return new RedirectResponse($this->generateUrl('_job_status', array('uid' => $job->getJobUid(), '_format' => 'html')));
    }

The status page automatically refresh using some JQuery code. It redirects to the results page when the job is finished.

Killing a job
~~~~~~~~~~~~~

Depending on the scheduler, it may be possible to kill a job (not supported by "local" scheduler). To do so, just use the jobKill action::

    public function yourAction() {
        // ...Do some stuff
        
        // Redirect to status page
        return new RedirectResponse($this->generateUrl('_job_kill', array('uid' => $job->getJobUid())));
    }

Viewing the results of a job
~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Once a job is finished, you can access the files specified when building the Job object. This is done by the _job_results action.

Viewing the history of jobs
~~~~~~~~~~~~~~~~~~~~~~~~~~~

The _job_history action allow to view all the job the current user has launched in the last few days (see history_length configuration).
The user needs to be logged in using any userprovider. Anonymous users don't have access to their history.

Overriding templates
~~~~~~~~~~~~~~~~~~~~

The templates provided by this bundle can be easily overriden using the standard Symfony mechanism.
Briefly, to customize the GenouestSchedulerBundle:Scheduler:results.html.twig template, you need to create the file app/Resources/GenouestSchedulerBundle/views/Scheduler/results.html.twig.

This is the list of templates that you can customize, with their description::

    GenouestSchedulerBundle:Scheduler:layout.html.twig -> General layout of the pages
    GenouestSchedulerBundle:Scheduler:status.html.twig -> Page displaying the status of a job
    GenouestSchedulerBundle:Scheduler:results.html.twig -> Page displaying the results of a job
    GenouestSchedulerBundle:Scheduler:kill.html.twig -> Page displayed when a job gets killed
    GenouestSchedulerBundle:Scheduler:error.html.twig -> Error page displayed when job submission failed
    GenouestSchedulerBundle:Scheduler:history.html.twig -> Page displaying all the jobs launched by the user
    GenouestSchedulerBundle:Scheduler:email.html.twig -> Content of the email sent when the jobs are finished
    GenouestSchedulerBundle:Scheduler:script_drmaa.sh.twig -> Bash script template used by the drmaa scheduler to launch the job command and send email if needed
    GenouestSchedulerBundle:Scheduler:script_local.sh.twig -> Bash script template used by the local scheduler to launch the job command and send email if needed

Using a specific Doctrine entity_manager
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

If you want to use a specific entity_manager, you need to override a service definition. At the end of config.yml, add the following lines:

    services:
        scheduler.entity_manager:
            alias: doctrine.orm.XX_entity_manager

Replace 'doctrine.orm.XX_entity_manager' by the service id of the correct entity manager.
This is an example of doctrine configuration with 2 entity managers, each one managing entities in a different database:

    # Doctrine Configuration
    doctrine:
        dbal:
            default_connection:     foo
            connections:
                foo:
                    driver:   %database_driver%
                    host:     %database_host%
                    dbname:   %database_name%
                    user:     %database_user%
                    password: %database_password%
                scheduler:
                    driver:   %database_driver_scheduler%
                    host:     %database_host_scheduler%
                    dbname:   %database_name_scheduler%
                    user:     %database_user_scheduler%
                    password: %database_password_scheduler%

        orm:
            default_entity_manager:   default
            entity_managers:
                default:
                    connection:       foo
                    mappings:
                        FooBundle: ~
                scheduler: # Replace XX by that
                    connection:       scheduler
                    mappings:
                        GenouestSchedulerBundle: ~

To make the GenouestSchedulerBundle use the correct entity manager, you need to define the service like this:

    services:
        scheduler.entity_manager:
            alias: doctrine.orm.scheduler_entity_manager

