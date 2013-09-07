[![Latest Stable Version](https://poser.pugx.org/mrimann/remote-script-verifier/v/stable.png)](https://packagist.org/packages/mrimann/remote-script-verifier)
[![Total Downloads](https://poser.pugx.org/mrimann/remote-script-verifier/downloads.png)](https://packagist.org/packages/mrimann/remote-script-verifier)
[![Build Status](https://travis-ci.org/mrimann/RemoteScriptVerifier.png)](https://travis-ci.org/mrimann/RemoteScriptVerifier)

## What does it do?

This package contains some helper classes to support a special kind of online-challenges where the participants have to create a script that solves some kind of things. The herein contained code helps the author of the quiz to verify the output and display some nice check results to the participant.

## How to use it?

Basically the script just needs to be fed with a valid URL that points to a a webserver on any server on the net, that serves a script under that URL, which solves the given tasks. So here are some snippets to include into your quiz page:

### Getting the URL and start the verifier

I expect you have a form, with an input field named "url" which is POSTed to the quiz-script. Within the quiz script, the following code initializes the verifier:

	$url = $_POST['url'];
	echo 'OK, let\'s try to verify the URL: "' . htmlspecialchars($url) . '"';
	$verifier = new \Mrimann\RemoteScriptVerifier\Verifier();
	$verifier->setBaseUrl($url);

### Some safety net

The net is full of very nice persons - but there are also some guys around that want to make trouble... For this reason, the RemoteScriptVerifier comes with some throttling and limiting feature. This means that a given script URL can only be requested n times per hour - and also only n requests from the same source IP address within an hour are allowed.

This feature must be explicitely enabled and configured. It's the only feature that needs a database to be created (it must contain a table called "logging", see ``res/logging.sql`` for the DB schema).

	// we want to use the throttling + logging stuff
	$verifier->setDatabaseHost('localhost');
	$verifier->setDatabaseUser('foobar');
	$verifier->setDatabasePassword('ultrastrongsecurity');
	$verifier->setDatabaseName('foobar');
	$verifier->enableThrottlingAndLogging();

	// if initializing would have failed, we would have some failed tests
	if ($verifier->passedAllTests() == TRUE) {
		$verifier->checkRequestAgainstThrottlingLimits(
			$_SERVER['REMOTE_ADDR'],
			$url
		);
	}

	// if the request did not pass the above barrier test, we should give up now
	if ($verifier->passedAllTests() != TRUE) {
		$verifier->addNewFailedResult('Given up for now...');
	}

### Perform some basic checks

The RemoteScriptVerifier package comes with some basic checks included, you can execute them like this:

	// check basic availability
	$basicChecks = new \Mrimann\RemoteScriptVerifier\Checks($verifier);
	$basicChecks->executeTestForNonEmptyUrl();
	$basicChecks->executeTestForValidLookingUrl();
	// this is sample content and depends on your challenge, what's needed
	$requiredGetParameters = array(
		'x' => rand(1,99),
		'y' => rand(1,99)
	);
	// checks if the script is returning a HTTP 200 status code if called with valid input
	$basicChecks->executeTestForAvailabilityOfTheRemoteScript($requiredGetParameters);
	if ($verifier->passedAllTests() != TRUE) {
		$verifier->addNewFailedResult('Giving up...');
	}

### Now perform your first own checks

Basically you can now request the remote script again and again, depending on the challenge you've set up. To verify the input validation, I'm running code like this:

	// check the input validation of the remote script
	$correctErrorReasonPhrase = 'I don\'t like your input...';
	$res = $verifier->fetchRemoteScript(
		array()
	);
	if ($res->getStatusCode() == 500 && $res->getReasonPhrase() == $correctErrorReasonPhrase) {
		$verifier->addNewSuccessfulResult('Throws error when called with no GET parameters.');
	} else {
		$verifier->addNewFailedResult('Uh, there seems to be missing some input validation!');
	}

As the challenge requests a script that takes two GET parameters, it must fail with none given. That's what we're checking with this code. Further you can see how to report errors in your own checks: Just call either ``addNewSuccessfulResult`` or ``addNewFailedResult`` with some descriptive text. These will later be shown to the user (see next section).

As soon as you've verified the script behaves correctly when throwing bullshit at it, it's time to verify that the paricipant of the quiz has successfully solved the challenge you've started. So for example the (ok, very simple) challenge could be "Take the two GET parameters x and y which are random integer values, return the sum of those two numbers" and the code to check that could be:

	// let's check the output of the script to be correct
	$x = rand(1, 999);
	$y = rand(1, 999);
	$res = $verifier->fetchRemoteScript(
		array(
			'x' => $x,
			'y' => $y
		)
	);
	// if you're about to conduct multple tests, save the response of the script
	// and check against this, instead of re-fetching the script from the remote
	// server over and over again
	$rawOoutput = $res->getBody();

	// check the calculation result
	if ($output == $x + $y) {
		$verifier->addNewSuccessfulResult('Your calculation looks fine.');
	} else {
		$verifier->addNewFailedResult('Looks like your calculation is not correct yet.');
	}


### Show the result to the user

Of course, the user wants to see a response on what he's achieved. So here's how to bring the results back to the participant:

	// show the check results
	echo '<ul class="results">';
	while ($checkResult = $verifier->getCheckResults()->current()) {
		echo '<li class="' . $checkResult->getStatus() . '">' . $checkResult->getMessage() . '</li>';
		$verifier->getCheckResults()->next();
	}
	echo '</ul>';

The above will output the single check results and if styled via CSS they will already indicate a green feeling in case everything went fine. But at the end the user is basically only interested in getting the link to the next stage, so let's give it to him or her:

	// show the link if all checks were passed
	if ($verifier->passedAllTests() === TRUE) {
		$verifier->logVerification('success');
		echo '<b>Yippie aye yeah. Here\'s the link to the final stage: <a href="#">GO GO GO</a></b>';
	} else {
		$verifier->logVerification(
			$verifier->getErrorCount() . ' errors'
		);
		echo '<b>Seems like something went wrong - sorry, there\'s no link for you now - try again...</b>';
	}

The above contains two calls to ``logVerification`` which is only needd in case you've activated the throttling and limiting feature above. It will extend the log entry which was created above with the final result of the verification - so you should see in the DB, how many tries one had and how many errors each version of his script produced when it was verified.

## How to support

If you find my stuff useful, I'd be very happy if you could check [my support page](http://rimann.org/support) to see how you could make me happy :-)

And of course, there is room to improve this whole code. If you find a bug, or think things could be simplified or just improved, just open up an issue - and submit a pull request if possible. That way everyone could benefit from your input.

## License

The whole code of this repository is licensed under ther permissive MIT license, see detailed information in the [LICENSE](https://github.com/mrimann/RemoteScriptVerifier/blob/master/LICENSE) file.