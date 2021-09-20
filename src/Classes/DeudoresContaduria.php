<?php

namespace Jota\DeudoresContaduria\Classes;

use Jota\DeudoresContaduria\Exceptions\LoadTimeException;
use Nesk\Puphpeteer\Puppeteer;
use Nesk\Rialto\Data\JsFunction;
use Nesk\Rialto\Exceptions\Node;

class DeudoresContaduria
{
    /**
     * contiene la instancia de la clase puppeteer
     * @var Puppeteer
     */
    private Puppeteer $puppeteer;

    /**
     * contiene el browser 
     */
    private $browser;

    /**
     * contiene la pagina donde
     * se realiza la busqueda
     */
    private $page;

    /**
     * Resultado de la busqueda
     * @var array
     */
    private array $result;

    /**
     * Resultado de la busqueda
     * @var array
     */
    private ?array $proxy;


    public function __construct(?array $proxy = null)
    {
        $this->puppeteer = new Puppeteer([
            'executable_path' => config('contaduria.node'),
        ]);

        $this->proxy = $proxy;
    }


    /**
     * Busca en la pagina de la contaduria un numero de cedula(Colombiana)
     * para saber si es un deudor moroso
     * @author alexander montaño
     * @param string $numero_cedula : identificacion a buscar
     * @param int $retries : numero de intentos
     * @return void
     */
    public function searchByCedula(string $cedula, int $retries = 1): void
    {
        if ($retries > 4) {
            $this->browser->close();
            throw new LoadTimeException('error tiempo de carga, la pagina no carga', ['cedula' => $cedula, 'proxy' => $this->proxy]);
        }

        try {

            if ($retries == 1)
                $this->loadBrowser();

            $this->page->tryCatch->goto('https://eris.contaduria.gov.co/BDME', ['waitUntil' => 'load', 'timeout' => 7000]);
            $this->page->screenshot(['path' => '/var/www/html/kredicity-nuevo/example.png']);
            $this->pageLoaded($cedula);
        } catch (Node\Exception $e) {
            $this->searchByCedula($cedula, ($retries + 1));
        }
    }

    /**
     * carga el browser segun se envie proxy o no
     * @author alexander montaño
     * @return void
     */
    private function loadBrowser(): void
    {
        if (!is_null($this->proxy)) {
            $this->browser = $this->puppeteer->launch([
                'headless' => false,
                'slowMo' => 50,
                'args' => [
                    '--proxy-server=' . $this->proxy['ip'] . ':' . $this->proxy['port'],
                    '--disable-gpu',
                    '--disable-setuid-sandbox',
                    '--no-sandbox',
                ]
            ]);
            $this->page = $this->browser->newPage();
            $this->page->authenticate([
                'username' =>  $this->proxy['user'],
                'password' => $this->proxy['password']
            ]);
        } else {

            $this->browser = $this->puppeteer->launch([
                'headless' => false,
                'slowMo' => 10,
                'args' => [
                    '--disable-gpu',
                    '--disable-setuid-sandbox',
                    '--no-sandbox',
                ]
            ]);
            $this->page = $this->browser->newPage();
        }
    }


    /**
     * si la pagina esta cargada correctamente
     * se ejecuta la busqueda
     * @author alexander montaño
     * @param string $cedula : cedula buscada
     * @return void
     */
    private function pageLoaded(string $cedula): void
    {
        try {
            $this->page->waitFor(1000);
            $this->page->evaluate(JsFunction::createWithBody("
                return document.getElementsByClassName('gwt-Anchor')[0].click()
            "));
            $this->page->waitFor(2000);

            $this->page->type('.gwt-TextBox', config('contaduria.user'));
            $this->page->type('.gwt-PasswordTextBox', config('contaduria.password'));
            $this->page->click('.gwt-Button');
            $this->page->waitFor(1000);

            $this->page->type('.gwt-TextBox', $cedula);
            $this->page->waitFor(500);
            $this->page->click('.gwt-Button');

            $this->page->evaluate(JsFunction::createWithBody("
                return document.getElementsByClassName('gwt-Button')[1].click()
            "));

            $this->page->waitFor(1000);

            if ((int)$cedula === (int)config('contaduria.user')) {
                $this->page->evaluate(JsFunction::createWithBody("
                    return document.getElementsByClassName('gwt-ListBox')[0].value = 1
                "));
            } else {
                $this->page->evaluate(JsFunction::createWithBody("
                    return document.getElementsByClassName('gwt-ListBox')[0].value = 3
                "));
            }

            $this->page->click('.gwt-Button');

            $this->page->waitFor(7000);

            $html_response = $this->page->tryCatch->evaluate(JsFunction::createWithBody("
                return document.getElementsByClassName('certificado-content')[0].innerHTML
            "));
            $this->browser->close();
            $this->setResult($html_response, $cedula);
        } catch (\Throwable $th) {
            throw new LoadTimeException('error carga, la pagina no cargo todos los elementos', ['cedula' => $cedula]);
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
            $this->result['response'] = [
                'response' => 'El documento de identificación número ' . $cedula . ' SI está incluido en el BDME que publica la CONTADURIA GENERAL DE LA NACIÓN, de acuerdo con lo establecido en el artículo 2° de la Ley 901 de 2004.',
            ];
            $this->whereIsReporte($response);
        } else {
            $this->result['is_registered'] = false;
            $this->result['response'] = [
                'response' => 'El documento de identificación número ' . $cedula . ' NO está incluido en el BDME que publica la CONTADURIA GENERAL DE LA NACIÓN, de acuerdo con lo establecido en el artículo 2° de la Ley 901 de 2004.',
            ];
        }
    }

    /**
     * toma el resultado y lo descompone para setear donde 
     * se encuentra reportado el numero de cedula
     * @author alexander montaño
     * @param string $response
     * @return void
     */
    private function whereIsReporte(string $response): void
    {
        $array_data = explode('<div style="outline-style:none;" __gwt_cell="cell-gwt-uid-', $response);
        $flag = 0;
        $count = 0;
        foreach ($array_data as $key => $data) {
            if ($key != 0) {
                if ($key == 1) {
                    $info = substr($data, 17);
                    $info = explode('</div>', $info);
                } else {
                    $info = substr($data, 4);
                    $info = explode('</div>', $info);
                }
                $this->addIfo($count, $flag, $info[0]);
                $flag += 1;
                if ($flag > 3) {
                    $flag = 0;
                    $count += 1;
                }
            } else {
                $info = explode('<div class="gwt-Label">:', $data);
                $info = explode('</div>', $info[1]);
                $this->result['entidad_reportante'] = $info[0];
            }
        }
    }

    /**
     * Agrega la informacion de cada una de las deudas
     * asociadas a el numero de cedula
     * @author alexander montaño
     * @param int $cont
     * @param int $flag
     * @param string $info
     * @return bool
     */
    private function addIfo(int $count, int $flag, string $info): void
    {
        if ($flag == 0) {
            $this->result['info'][$count]['nombre_reportado'] = $info;
        } elseif ($flag == 1) {
            $this->result['info'][$count]['numero_obligacion'] = $info;
        } elseif ($flag == 2) {
            $this->result['info'][$count]['estado'] = $info;
        } elseif ($flag == 3) {
            $this->result['info'][$count]['fecha_corte'] = $info;
        }
    }

    /**
     * setea si el numero de cedula registra
     * por lo menos una deuda
     * @author alexander montaño
     * @param string $response
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
