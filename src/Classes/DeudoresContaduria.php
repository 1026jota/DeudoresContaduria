<?php

namespace Jota\DeudoresContaduria\Classes;

use Nesk\Puphpeteer\Puppeteer;
use Nesk\Rialto\Data\JsFunction;
use Nesk\Rialto\Exceptions\Node\FatalException;
use Nesk\Rialto\Exceptions\Node;
class DeudoresContaduria
{
    /**
     * contiene la instancia de la clase puppeteer
     * @var Puppeteer
     */
    private Puppeteer $puppeteer;

    /**
     * Resultado de la busqueda
     * @var array
     */
    private array $result;


    public function __construct()
    {
        $this->puppeteer = new Puppeteer([
            'executable_path' => config('contaduria.node'),
        ]);
        $this->time = 3;
    }

    /**
     * Busca en la pagina de la contaduria un numero de cedula(Colombiana)
     * para saber si es un deudor moroso
     * @author alexander montaño
     * @param string $numero_cedula : identificacion a buscar
     * @return void
     */
    public function searchByCedula(string $cedula, $time = 2): void
    {
        try {

            $browser = $this->puppeteer->launch([
                'headless' => true,

                'args' => [
                    '--disable-gpu',
                    '--disable-setuid-sandbox',
                    '--no-sandbox',
                ]
            ]);
            $page = $browser->newPage();

            $page->goto('https://eris.contaduria.gov.co/BDME/');

            $page->waitForSelector('.gwt-Anchor');
            $page->evaluate(JsFunction::createWithBody("
                return document.getElementsByClassName('gwt-Anchor')[0].click()
            "));
            $page->waitFor(500);

            $page->type('.gwt-TextBox', config('contaduria.user'));
            $page->type('.gwt-PasswordTextBox', config('contaduria.password'));
            $page->click('.gwt-Button');
            $page->waitFor(1000);

            $page->type('.gwt-TextBox', $cedula);
            $page->click('.gwt-Button');
            $page->waitFor(1000);


            $page->evaluate(JsFunction::createWithBody("
                return document.getElementsByClassName('gwt-Button')[1].click()
            "));
            $page->waitFor(2000);

            if ((int)$cedula === (int)config('contaduria.user')) {
                $page->evaluate(JsFunction::createWithBody("
                    return document.getElementsByClassName('gwt-ListBox')[0].value = 1
                "));
            } else {
                $page->evaluate(JsFunction::createWithBody("
                    return document.getElementsByClassName('gwt-ListBox')[0].value = 3
                "));
            }
            $page->click('.gwt-Button');

            $page->waitFor($time*1000);

            $html_response = $page->tryCatch->evaluate(JsFunction::createWithBody("
                return document.getElementsByClassName('certificado-content')[0].innerHTML
            "));
            $this->setResult($html_response, $cedula);
            $browser->close();
        }catch (Node\Exception $exception) {
            $browser->close();
            $this->time += 2;
            $this->searchByCedula($cedula, $this->time);
        }
    }

    /**
     * organiza el resultado de que se entrega al cliente
     * segun la respuesta obtenida en la pagina de la contaduria
     * @author alexander montaño
     * @param string $response : respuesta procuraduria
     * @param string $cedula : cedula buscada
     * @return void
     */
    private function setResult(string $response, string $cedula): void
    {
        if ($this->isDeudor($response)) {
            $this->result['is_registered'] = true;
            $this->result['result'] = [
                'response' => 'El documento de identificación número ' . $cedula . ' SI está incluido en el BDME que publica la CONTADURIA GENERAL DE LA NACIÓN, de acuerdo con lo establecido en el artículo 2° de la Ley 901 de 2004.',
            ];
        } else {
            $this->result['is_registered'] = false;
            $this->result['result'] = [
                'response' => 'El documento de identificación número ' . $cedula . ' NO está incluido en el BDME que publica la CONTADURIA GENERAL DE LA NACIÓN, de acuerdo con lo establecido en el artículo 2° de la Ley 901 de 2004.',
            ];
        }
    }

    /**
     * Responde true si el numero de cedula se encuentra registrado
     * como deudor en la BD de la contaduria
     * @author alexander montaño
     * @return bool
     */
    private function isDeudor(string $response): bool
    {
        $response = explode('<strong>', $response);
        $yes_or_not = substr($response[1], 0, 2);
        if ($yes_or_not === 'SI')
            return true;
        else
            return false;
    }

    /**
     * Retorna el array con la repuesta
     * obtenida
     * @author alexander montaño
     * @return array
     */
    public function getResult(): array
    {
        return $this->result;
    }
}
