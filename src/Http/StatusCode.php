<?php declare(strict_types=1);

namespace Kirameki\Http;

class StatusCode
{
    public const OK = 200;
    public const Created = 201;
    public const Accepted = 202;
    public const NonAuthoritativeInformation = 203;
    public const NoContent = 204;
    public const ResetContent = 205;
    public const PartialContent = 206;
    public const MultiStatus = 207;
    public const AlreadyReported = 208;
    public const ImUsed = 226;
    public const MultipleChoice = 300;
    public const MovedPermanently = 301;
    public const Found = 302;
    public const SeeOther = 303;
    public const NotModified = 304;
    public const UseProxy = 305;
    public const SwitchProxy = 306;
    public const TemporaryRedirect = 307;
    public const PermanentRedirect = 308;
    public const BadRequest = 400;
    public const Unauthorized = 401;
    public const PaymentRequired = 402;
    public const Forbidden = 403;
    public const NotFound = 404;
    public const MethodNotAllowed = 405;
    public const NotAcceptable = 406;
    public const ProxyAuthenticationRequired = 407;
    public const RequestTimeout = 408;
    public const Conflict = 409;
    public const Gone = 410;
    public const LengthRequired = 411;
    public const PreconditionFailed = 412;
    public const PayloadTooLarge = 413;
    public const UriTooLong = 414;
    public const UnsupportedMediaType = 415;
    public const RangeNotSatisfiable = 416;
    public const ExpectationFailed = 417;
    public const ImATeaPot = 418;
    public const MisdirectedRequest = 421;
    public const UnprocessableEntity = 422;
    public const Locked = 423;
    public const FailedDependency = 424;
    public const TooEarly = 425;
    public const UpgradeRequired = 426;
    public const PreconditionRequired = 428;
    public const TooManyRequests = 429;
    public const RequestHeaderFieldsTooLarge = 431;
    public const UnavailableForLegalReasons = 451;
    public const InternalServerError = 500;
    public const NotImplemented = 501;
    public const BadGateway = 502;
    public const ServiceUnavailable = 503;
    public const GatewayTimeout = 504;
    public const HttpVersionNotSupported = 505;
    public const VariantAlsoNegotiates = 506;
    public const InsufficientStorage = 507;
    public const LoopDetected = 508;
    public const NotExtended = 510;
    public const NetworkAuthenticationRequired = 511;

    /**
     * @param int $code
     * @return string
     */
    public static function asPhrase(int $code): string
    {
        return match ($code) {
            self::OK => 'OK',
            self::Created => 'Created',
            self::Accepted => 'Accepted',
            self::NonAuthoritativeInformation => 'Non-Authoritative Information',
            self::NoContent => 'No Content',
            self::ResetContent => 'Reset Content',
            self::PartialContent => 'Partial Content',
            self::MultiStatus => 'Multi-Status',
            self::AlreadyReported => 'Already Reported',
            self::ImUsed => 'IM Used',
            self::MultipleChoice => 'Multiple Choice',
            self::MovedPermanently => 'Moved Permanently',
            self::Found => 'Found',
            self::SeeOther => 'See Other',
            self::NotModified => 'Not Modified',
            self::UseProxy => 'Use Proxy',
            self::SwitchProxy => 'Switch Proxy',
            self::TemporaryRedirect => 'Temporary Redirect',
            self::PermanentRedirect => 'Permanent Redirect',
            self::BadRequest => 'Bad Request',
            self::Unauthorized => 'Unauthorized',
            self::PaymentRequired => 'Payment Required',
            self::Forbidden => 'Forbidden',
            self::NotFound => 'Not Found',
            self::MethodNotAllowed => 'Method Not Allowed',
            self::NotAcceptable => 'Not Acceptable',
            self::ProxyAuthenticationRequired => 'Proxy Authentication Required',
            self::RequestTimeout => 'Request Timeout',
            self::Conflict => 'Conflict',
            self::Gone => 'Gone',
            self::LengthRequired => 'Length Required',
            self::PreconditionFailed => 'Precondition Failed',
            self::PayloadTooLarge => 'Payload Too Large',
            self::UriTooLong => 'URI Too Long',
            self::UnsupportedMediaType => 'Unsupported Media Type',
            self::RangeNotSatisfiable => 'Range Not Satisfiable',
            self::ExpectationFailed => 'Expectation Failed',
            self::ImATeaPot => 'I\'m a teapot',
            self::MisdirectedRequest => 'Misdirected Request',
            self::UnprocessableEntity => 'Unprocessable Entity',
            self::Locked => 'Locked',
            self::FailedDependency => 'Failed Dependency',
            self::TooEarly => 'Too Early',
            self::UpgradeRequired => 'Upgrade Required',
            self::PreconditionRequired => 'Precondition Required',
            self::TooManyRequests => 'Too Many Requests',
            self::RequestHeaderFieldsTooLarge => 'Request Header Fields Too Large',
            self::UnavailableForLegalReasons => 'Unavailable For Legal Reasons',
            self::InternalServerError => 'Internal Server Error',
            self::NotImplemented => 'Not Implemented',
            self::BadGateway => 'Bad Gateway',
            self::ServiceUnavailable => 'Service Unavailable',
            self::GatewayTimeout => 'Gateway Timeout',
            self::HttpVersionNotSupported => 'HTTP Version Not Supported',
            self::VariantAlsoNegotiates => 'Variant Also Negotiates',
            self::InsufficientStorage => 'Insufficient Storage',
            self::LoopDetected => 'Loop Detected',
            self::NotExtended => 'Not Extended',
            self::NetworkAuthenticationRequired => 'Network Authentication Required',
        };
    }
}
