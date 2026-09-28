<?php

namespace TypiCMS\Modules\Pages\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use TypiCMS\Modules\Core\Facades\TypiCMS;
use TypiCMS\Modules\Core\Http\Controllers\BasePublicController;
use TypiCMS\Modules\Pages\Models\Page;
use TypiCMS\Modules\News\Models\News;
use TypiCMS\Modules\Banners\Models\Banner;
use TypiCMS\Modules\Newsletters\Models\Newsletter;
use TypiCMS\Modules\Newsletters\Http\Requests\FormRequest;
use Illuminate\Http\RedirectResponse;
use TypiCMS\Modules\Pages\Http\Requests\ContactRequest;
use App\Mail\ContactMail;
use Illuminate\Support\Facades\Mail;

class PublicController extends BasePublicController
{
    /**
     * Page uri : lang/slug.
     *
     * @param mixed|null $uri
     *
     * @return \Illuminate\Http\Response | \Illuminate\Http\RedirectResponse
     */
    public function uri($uri = null)
    { 
        $page = $this->findPageByUri($uri);
        $highlightNews=News::published()
        ->highlight()
        ->order()
        ->take(3)
        ->get();

        if($highlightNews->count()<1){
            $highlightNews=News::published()
            ->order()
            ->take(3)
            ->get();
        }

        $banners= Banner::published()->get();

        abort_if(!$page, '404');

        if ($page->private && !Auth::check()) {
            return redirect()->guest(route(app()->getLocale().'::login'));
        }

        if ($page->redirect && $page->publishedSubpages->count() > 0) {
            $childUri = $page->publishedSubpages->first()->uri();

            return redirect($childUri);
        }

        // get submenu
        $children = $page->getSubMenu();

        $templateDir = 'pages::'.config('typicms.template_dir', 'public').'.';
        $template = $page->template ?: 'default';

        if (!view()->exists($templateDir.$template)) {
            info('Template '.$template.' not found, switching to default template.');
            $template = 'default';
        }

        return view($templateDir.$template, compact('children', 'page', 'highlightNews', 'banners'));
    }

    /**
     * Find page by URI.
     *
     * @param mixed $uri
     *
     * @return TypiCMS\Modules\Pages\Models\Page
     */
    private function findPageByUri($uri)
    {
        $query = Page::published()
            ->with([
                'image',
                'images',
                'documents',
                'publishedSections.image',
                'publishedSections.images',
                'publishedSections.documents',
            ]);

        if ($uri === null) {
            return $query->where('is_home', 1)->firstOrFail();
        }

        // Only locale in url
        if (
            in_array($uri, locales()) &&
            (
                TypiCMS::mainLocale() !== $uri ||
                config('typicms.main_locale_in_url')
            )
        ) {
            return $query->where('is_home', 1)->firstOrFail();
        }

        $query->published();

        $query->whereUriIs($uri);

        return $query->firstOrFail();
    }

    /**
     * Get browser language or default locale and redirect to homepage.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirectToHomepage()
    {
        $homepage = Page::published()->where('is_home', 1)->firstOrFail();
        $locale = $this->getBrowserLanguageOrDefault();

        return redirect($homepage->uri($locale));
    }

    /**
     * Get browser language or app.locale.
     *
     * @return string
     */
    private function getBrowserLanguageOrDefault()
    {
        if ($browserLanguage = getenv('HTTP_ACCEPT_LANGUAGE')) {
            $browserLocale = mb_substr($browserLanguage, 0, 2);
            if (in_array($browserLocale, TypiCMS::enabledLocales())) {
                return $browserLocale;
            }
        }

        return config('app.locale');
    }

    /**
     * Display the lang chooser.
     */
    public function langChooser()
    {
        $homepage = Page::published()->where('is_home', 1)->first();
        if (!$homepage) {
            app('log')->error('No homepage found.');
            abort(404);
        }
        $locales = TypiCMS::enabledLocales();

        return view('core::public.lang-chooser')
            ->with(compact('homepage', 'locales'));
    }

    public function store(FormRequest $request): RedirectResponse
    {

        $newsletter = Newsletter::create($request->validated());

        return redirect()->back();   
    }

    public function contactAdmin(ContactRequest $request): RedirectResponse
    {
        Mail::to('info@au.unibl.org')->send(new ContactMail($request->all()));
        return back()
        ->with('success',__('Mail sent'));

        return redirect()->back();   
    }

}
