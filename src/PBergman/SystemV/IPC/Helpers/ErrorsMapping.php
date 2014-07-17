<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */

namespace PBergman\SystemV\IPC\Helpers;

/**
 * Class ErrorsMapping
 *
 * @package PBergman\SystemV\IPC\SystemErrors
 */
class ErrorsMapping
{
    const EPERM = 1;
    const ENOENT = 2;
    const ESRCH = 3;
    const EINTR = 4;
    const EIO = 5;
    const ENXIO = 6;
    const E2BIG = 7;
    const ENOEXEC = 8;
    const EBADF = 9;
    const ECHILD = 10;
    const EAGAIN = 11;
    const ENOMEM = 12;
    const EACCES = 13;
    const EFAULT = 14;
    const ENOTBLK = 15;
    const EBUSY = 16;
    const EEXIST = 17;
    const EXDEV = 18;
    const ENODEV = 19;
    const ENOTDIR = 20;
    const EISDIR = 21;
    const EINVAL = 22;
    const ENFILE = 23;
    const EMFILE = 24;
    const ENOTTY = 25;
    const ETXTBSY = 26;
    const EFBIG = 27;
    const ENOSPC = 28;
    const ESPIPE = 29;
    const EROFS = 30;
    const EMLINK = 31;
    const EPIPE = 32;
    const EDOM = 33;
    const ERANGE = 34;
    const EDEADLK = 35;
    const ENAMETOOLONG = 36;
    const ENOLCK = 37;
    const ENOSYS = 38;
    const ENOTEMPTY = 39;
    const ELOOP = 40;
    const ENOMSG = 42;
    const EIDRM = 43;
    const ECHRNG = 44;
    const EL2NSYNC = 45;
    const EL3HLT = 46;
    const EL3RST = 47;
    const ELNRNG = 48;
    const EUNATCH = 49;
    const ENOCSI = 50;
    const EL2HLT = 51;
    const EBADE = 52;
    const EBADR = 53;
    const EXFULL = 54;
    const ENOANO = 55;
    const EBADRQC = 56;
    const EBADSLT = 57;
    const EBFONT = 59;
    const ENOSTR = 60;
    const ENODATA = 61;
    const ETIME = 62;
    const ENOSR = 63;
    const ENONET = 64;
    const ENOPKG = 65;
    const EREMOTE = 66;
    const ENOLINK = 67;
    const EADV = 68;
    const ESRMNT = 69;
    const ECOMM = 70;
    const EPROTO = 71;
    const EMULTIHOP = 72;
    const EDOTDOT = 73;
    const EBADMSG = 74;
    const EOVERFLOW = 75;
    const ENOTUNIQ = 76;
    const EBADFD = 77;
    const EREMCHG = 78;
    const ELIBACC = 79;
    const ELIBBAD = 80;
    const ELIBSCN = 81;
    const ELIBMAX = 82;
    const ELIBEXEC = 83;
    const EILSEQ = 84;
    const ERESTART = 85;
    const ESTRPIPE = 86;
    const EUSERS = 87;
    const ENOTSOCK = 88;
    const EDESTADDRREQ = 89;
    const EMSGSIZE = 90;
    const EPROTOTYPE = 91;
    const ENOPROTOOPT = 92;
    const EPROTONOSUPPORT = 93;
    const ESOCKTNOSUPPORT = 94;
    const EOPNOTSUPP = 95;
    const EPFNOSUPPORT = 96;
    const EAFNOSUPPORT = 97;
    const EADDRINUSE = 98;
    const EADDRNOTAVAIL = 99;
    const ENETDOWN = 100;
    const ENETUNREACH = 101;
    const ENETRESET = 102;
    const ECONNABORTED = 103;
    const ECONNRESET = 104;
    const ENOBUFS = 105;
    const EISCONN = 106;
    const ENOTCONN = 107;
    const ESHUTDOWN = 108;
    const ETOOMANYREFS = 109;
    const ETIMEDOUT = 110;
    const ECONNREFUSED = 111;
    const EHOSTDOWN = 112;
    const EHOSTUNREACH = 113;
    const EALREADY = 114;
    const EINPROGRESS = 115;
    const ESTALE = 116;
    const EUCLEAN = 117;
    const ENOTNAM = 118;
    const ENAVAIL = 119;
    const EISNAM = 120;
    const EREMOTEIO = 121;
    const EDQUOT = 122;
    const ENOMEDIUM = 123;
    const EMEDIUMTYPE = 124;

    private static $ERROR_MAPPING = array(
        self::EPERM => 'Operation not permitted',
        self::ENOENT => 'No such file or directory',
        self::ESRCH => 'No such process',
        self::EINTR => 'Interrupted system call',
        self::EIO => 'I/O error',
        self::ENXIO => 'No such device or address',
        self::E2BIG => 'Arg list too long',
        self::ENOEXEC => 'Exec format error',
        self::EBADF => 'Bad file number',
        self::ECHILD => 'No child processes',
        self::EAGAIN => 'Try again',
        self::ENOMEM => 'Out of memory',
        self::EACCES => 'Permission denied',
        self::EFAULT => 'Bad address',
        self::ENOTBLK => 'Block device required',
        self::EBUSY => 'Device or resource busy',
        self::EEXIST => 'File exists',
        self::EXDEV => 'Cross-device link',
        self::ENODEV => 'No such device',
        self::ENOTDIR => 'Not a directory',
        self::EISDIR => 'Is a directory',
        self::EINVAL => 'Invalid argument',
        self::ENFILE => 'File table overflow',
        self::EMFILE => 'Too many open files',
        self::ENOTTY => 'Not a typewriter',
        self::ETXTBSY => 'Text file busy',
        self::EFBIG => 'File too large',
        self::ENOSPC => 'No space left on device',
        self::ESPIPE => 'Illegal seek',
        self::EROFS => 'Read-only file system',
        self::EMLINK => 'Too many links',
        self::EPIPE => 'Broken pipe',
        self::EDOM => 'Math argument out of domain of func',
        self::ERANGE => 'Math result not representable',
        self::EDEADLK => 'Resource deadlock would occur',
        self::ENAMETOOLONG => 'File name too long',
        self::ENOLCK => 'No record locks available',
        self::ENOSYS => 'Function not implemented',
        self::ENOTEMPTY => 'Directory not empty',
        self::ELOOP => 'Too many symbolic links encountered',
        self::ENOMSG => 'No message of desired type',
        self::EIDRM => 'Identifier removed',
        self::ECHRNG => 'Channel number out of range',
        self::EL2NSYNC => 'Level 2 not synchronized',
        self::EL3HLT => 'Level 3 halted',
        self::EL3RST => 'Level 3 reset',
        self::ELNRNG => 'Link number out of range',
        self::EUNATCH => 'Protocol driver not attached',
        self::ENOCSI => 'No CSI structure available',
        self::EL2HLT => 'Level 2 halted',
        self::EBADE => 'Invalid exchange',
        self::EBADR => 'Invalid request descriptor',
        self::EXFULL => 'Exchange full',
        self::ENOANO => 'No anode',
        self::EBADRQC => 'Invalid request code',
        self::EBADSLT => 'Invalid slot',
        self::EBFONT => 'Bad font file format',
        self::ENOSTR => 'Device not a stream',
        self::ENODATA => 'No data available',
        self::ETIME => 'Timer expired',
        self::ENOSR => 'Out of streams resources',
        self::ENONET => 'Machine is not on the network',
        self::ENOPKG => 'Package not installed',
        self::EREMOTE => 'Object is remote',
        self::ENOLINK => 'Link has been severed',
        self::EADV => 'Advertise error',
        self::ESRMNT => 'Srmount error',
        self::ECOMM => 'Communication error on send',
        self::EPROTO => 'Protocol error',
        self::EMULTIHOP => 'Multihop attempted',
        self::EDOTDOT => 'RFS specific error',
        self::EBADMSG => 'Not a data message',
        self::EOVERFLOW => 'Value too large for defined data type',
        self::ENOTUNIQ => 'Name not unique on network',
        self::EBADFD => 'File descriptor in bad state',
        self::EREMCHG => 'Remote address changed',
        self::ELIBACC => 'Can not access a needed shared library',
        self::ELIBBAD => 'Accessing a corrupted shared library',
        self::ELIBSCN => '.lib section in a.out corrupted',
        self::ELIBMAX => 'Attempting to link in too many shared libraries',
        self::ELIBEXEC => 'Cannot exec a shared library directly',
        self::EILSEQ => 'Illegal byte sequence',
        self::ERESTART => 'Interrupted system call should be restarted',
        self::ESTRPIPE => 'Streams pipe error',
        self::EUSERS => 'Too many users',
        self::ENOTSOCK => 'Socket operation on non-socket',
        self::EDESTADDRREQ => 'Destination address required',
        self::EMSGSIZE => 'Message too long',
        self::EPROTOTYPE => 'Protocol wrong type for socket',
        self::ENOPROTOOPT => 'Protocol not available',
        self::EPROTONOSUPPORT => 'Protocol not supported',
        self::ESOCKTNOSUPPORT => 'Socket type not supported',
        self::EOPNOTSUPP => 'Operation not supported on transport endpoint',
        self::EPFNOSUPPORT => 'Protocol family not supported',
        self::EAFNOSUPPORT => 'Address family not supported by protocol',
        self::EADDRINUSE => 'Address already in use',
        self::EADDRNOTAVAIL => 'Cannot assign requested address',
        self::ENETDOWN => 'Network is down',
        self::ENETUNREACH => 'Network is unreachable',
        self::ENETRESET => 'Network dropped connection because of reset',
        self::ECONNABORTED => 'Software caused connection abort',
        self::ECONNRESET => 'Connection reset by peer',
        self::ENOBUFS => 'No buffer space available',
        self::EISCONN => 'Transport endpoint is already connected',
        self::ENOTCONN => 'Transport endpoint is not connected',
        self::ESHUTDOWN => 'Cannot send after transport endpoint shutdown',
        self::ETOOMANYREFS => 'Too many references: cannot splice',
        self::ETIMEDOUT => 'Connection timed out',
        self::ECONNREFUSED => 'Connection refused',
        self::EHOSTDOWN => 'Host is down',
        self::EHOSTUNREACH => 'No route to host',
        self::EALREADY => 'Operation already in progress',
        self::EINPROGRESS => 'Operation now in progress',
        self::ESTALE => 'Stale NFS file handle',
        self::EUCLEAN => 'Structure needs cleaning',
        self::ENOTNAM => 'Not a XENIX named type file',
        self::ENAVAIL => 'No XENIX semaphores available',
        self::EISNAM => 'Is a named type file',
        self::EREMOTEIO => 'Remote I/O error',
        self::EDQUOT => 'Quota exceeded',
        self::ENOMEDIUM => 'No medium found',
        self::EMEDIUMTYPE => 'Wrong medium type',
    );

    /**
     * get representation from the given error code
     *
     * @param $code
     * @return mixed
     */
    static function getMessage($code)
    {
        return static::$ERROR_MAPPING[$code];
    }
}

