<?php

require dirname(__DIR__) . "/vendor/autoload.php";

//use Revolt\EventLoop;
//
//$suspension = EventLoop::getSuspension();
//
//EventLoop::delay(5, function () use ($suspension): void {
//    print '++ Executing callback created by EventLoop::delay()' . PHP_EOL;
//
//    $suspension->resume(null);
//});
//
//
//
//EventLoop::repeat(1, function () : void {
//    print 'ping' . PHP_EOL;
//});
//
//print '++ Suspending to event loop...' . PHP_EOL;
//
//$suspension->suspend();
//
//print '++ Script end' . PHP_EOL;




//use Revolt\EventLoop;
//
//if (\stream_set_blocking(STDIN, false) !== true) {
//    \fwrite(STDERR, "Unable to set STDIN to non-blocking" . PHP_EOL);
//    exit(1);
//}
//
//print "Write something and hit enter" . PHP_EOL;
//
//$suspension = EventLoop::getSuspension();
//
//$readableId = EventLoop::onReadable(STDIN, function ($id, $stream) use ($suspension): void {
////    EventLoop::cancel($id);
//
//    $chunk = \fread($stream, 8192);
//
//    print "Read " . \strlen($chunk) . " bytes" . PHP_EOL;
//
////    $suspension->resume(null);
//});
//
//$timeoutId = EventLoop::delay(15, function () use ($readableId, $suspension) {
//    EventLoop::cancel($readableId);
//
//    print "Timeout reached" . PHP_EOL;
//
//    $suspension->resume(null);
//});
//
//$suspension->suspend();
//
//EventLoop::cancel($readableId);
//EventLoop::cancel($timeoutId);





//$cache = new \Amp\Cache\LocalCache(100);
//$cache->set('name', 'dfdfdf');
//
//$c = $cache->get('name');
//
//var_dump($c);
