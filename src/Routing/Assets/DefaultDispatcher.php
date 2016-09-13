<?php

namespace Nova\Routing\Assets;

use Nova\Config\Config;
use Nova\Http\Response;
use Nova\Routing\Assets\DispatcherInterface;
use Nova\Support\Str;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesser;

use Carbon\Carbon;

use LogicException;


class DefaultDispatcher implements DispatcherInterface
{
    /**
     * The currently accepted encodings for Response content compression.
     *
     * @var array
     */
    protected static $algorithms = array('gzip', 'deflate');


    /**
     * Create a new Default Dispatcher instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Dispatch a Assets File Response.
     *
     * @return \Symfony\Component\HttpFoundation\Response|null
     */
    public function dispatch(SymfonyRequest $request)
    {
        // For proper Assets serving, the file URI should be either of the following:
        //
        // /templates/default/assets/css/style.css
        // /modules/blog/assets/css/style.css
        // /assets/css/style.css

        $uri = $request->path();

        // Calculate the Asset File path, if it is a valid one.
        $filePath = null;

        if (preg_match('#^(templates|modules)/([^/]+)/assets/(.*)$#i', $uri, $matches)) {
            $path = $matches[3];

            //
            $baseFolder = (strtolower($matches[1]) == 'modules') ? 'Modules' : 'Templates';

            $basePath = APPDIR .$baseFolder .DS .Str::studly($matches[2]) .DS .'Assets' .DS;

            $filePath = $basePath .str_replace('/', DS, $path);
        } else if (preg_match('#^(assets|vendor)/(.*)$#i', $uri, $matches)) {
            $path = $matches[2];

            //
            $baseFolder = strtolower($matches[1]);

            if (($baseFolder == 'vendor') && ! Str::startsWith($path, $this->paths)) {
                // The current URI is not a valid Vendor path; nothing to do.
            } else {
                $filePath = ROOTDIR .$baseFolder .DS .str_replace('/', DS, $path);
            }
        }

        if (! is_null($filePath) && in_array($request->method(), array('GET', 'HEAD'))) {
            $response = $this->serve($filePath, $request);

            // Prepare the Response instance.
            $response->prepare($request);

            return $response;
        }
    }

    /**
     * Serve a File.
     *
     * @param string $path
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function serve($path, SymfonyRequest $request)
    {
        if (! file_exists($path)) {
            return new Response('File Not Found', 404);
        } else if (! is_readable($path)) {
            return new Response('Unauthorized Access', 403);
        }

        // Collect the current file information.
        $guesser = MimeTypeGuesser::getInstance();

        // Even the Symfony's HTTP Foundation have troubles with the CSS and JS files?
        //
        // Hard coding the correct mime types for presently needed file extensions.
        switch ($fileExt = pathinfo($path, PATHINFO_EXTENSION)) {
            case 'css':
                $contentType = 'text/css';

                break;
            case 'js':
                $contentType = 'application/javascript';

                break;
            default:
                $contentType = $guesser->guess($path);

                break;
        }

        if (str_is('text/*', $contentType) || ($contentType == 'application/javascript')) {
            $response = $this->createFileResponse($path, $request);
        } else {
            $response = $this->createBinaryFileResponse($path);
        }

        // Set the Content type.
        $response->headers->set('Content-Type', $contentType);

        // Set the Cache Control.
        $cacheTime = Config::get('routing.assets.cacheTime', 10800);

        $response->setTtl(600);
        $response->setMaxAge($cacheTime);
        $response->setSharedMaxAge(600);

        // Prepare against the Request instance.
        $response->isNotModified($request);

        return $response;
    }

    protected function createFileResponse($path, SymfonyRequest $request)
    {
        // Get the accepted encodings from Request instance.
        $acceptEncoding = $request->headers->get('Accept-Encoding');

        if (! is_null($acceptEncoding)) {
            $acceptEncoding = array_map('trim', explode(',', $acceptEncoding));
        } else {
            $acceptEncoding = array();
        }

        // Create a Response instance.
        $response = new Response(file_get_contents($path), 200);

        // Setup the Last-Modified header.
        $lastModified = Carbon::createFromTimestampUTC(filemtime($path));

        $response->headers->set('Last-Modified', $lastModified->format('D, j M Y H:i:s') .' GMT');

        return $this->compressResponseContent($response, $acceptEncoding);
    }

    protected function createBinaryFileResponse($path, $contentDisposition = null)
    {
        $contentDisposition = $contentDisposition ?: 'inline';

        return new BinaryFileResponse($path, 200, array(), true, $contentDisposition, true, false);
    }

    protected function compressResponseContent(SymfonyResponse $response, array $acceptEncoding)
    {
        // Calculate the available algorithms.
        $algorithms = array_values(array_intersect($acceptEncoding, static::$algorithms));

        // If there are no available compression algorithms, just return the Response instance.
        if (empty($algorithms)) {
            $response->headers->set('Content-Length', strlen($response->getContent()));

            return $response;
        }

        // Get the (first) compression algorithm.
        $algorithm = array_shift($algorithms);

        // Compress the Response content.
        if ($algorithm == 'gzip') {
            $content = gzencode($response->getContent(), -1, FORCE_GZIP);
        } else if ($algorithm == 'deflate') {
            $content = gzencode($response->getContent(), -1, FORCE_DEFLATE);
        } else {
            throw new LogicException('Unknow encoding algorithm: ' .$algorithm);
        }

        // Setup the (new) Response content.
        $response->setContent($content);

        // Setup the (new) Content Length.
        $response->headers->set('Content-Length', strlen($content));

        // Setup the Content Encoding.
        $response->headers->set('Content-Encoding', $algorithm);

        return $response;
    }
    
}
